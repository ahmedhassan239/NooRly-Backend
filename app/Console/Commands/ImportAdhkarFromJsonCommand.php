<?php

namespace App\Console\Commands;

use App\Domain\Adhkar\Adhkar;
use App\Domain\Categories\Models\CategoryTranslation;
use App\Domain\Duas\Dua;
use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SAFE, non-destructive Adhkar import from Adhkar_Categories_Noorly.json.
 *
 * Hard rules:
 * - Never change categories with scope_id=3 (duas). Never change Dua data.
 * - Only work with scope_id=4 for Adhkar. Match by scope_id=4 + slug = JSON category.id.
 * - If no matching category exists for scope_id=4, INSERT new category (do not repurpose scope_id=3).
 * - Upsert category_translations only for resolved Adhkar category_ids.
 * - Import adhkar items; idempotency by (category_id, category_key, position).
 * - Insert categorizables rows linking category_id to Adhkar (categorizable_type = Adhkar::class).
 *
 * Dry-run by default; use --execute to perform the import.
 */
class ImportAdhkarFromJsonCommand extends Command
{
    protected $signature = 'adhkar:import-from-json
                            {--file= : Path to Adhkar_Categories_Noorly.json}
                            {--execute : Run the import (default is dry-run only)}
                            {--connection= : DB connection (default)}';

    protected $description = 'Safe Adhkar import: dry-run first (scope 3/4 report), then --execute to import categories, adhkar, and categorizables';

    /** content_scopes: duas = 3, adhkar = 4 */
    private const DUAS_SCOPE_ID = 3;
    private const ADHKAR_SCOPE_ID = 4;

    /** Exact Adhkar model class for categorizables.categorizable_type */
    private const ADHKAR_MODEL_CLASS = Adhkar::class;

    private string $connection;

    private int $jsonCategoryCount = 0;
    private int $jsonItemCount = 0;

    /** Dry-run: categories with scope_id in (3,4) and usage */
    private array $categoriesScope3 = [];
    private array $categoriesScope4 = [];
    private array $categoryIdsUsedByDua = [];
    private array $categoryIdsUsedByAdhkar = [];
    /** JSON category id => resolved category_id (scope_id=4 only) */
    private array $jsonCategoryIdToResolvedId = [];
    /** What would be created/updated */
    private int $wouldCreateCategories = 0;
    private int $wouldReuseCategories = 0;
    private int $wouldInsertAdhkar = 0;
    private int $wouldUpdateAdhkar = 0;
    private int $wouldInsertCategorizables = 0;
    private int $wouldSkipConflict = 0;

    /** Execute: counters */
    private int $categoriesCreated = 0;
    private int $categoriesReused = 0;
    private int $translationsInserted = 0;
    private int $translationsUpdated = 0;
    private int $adhkarInserted = 0;
    private int $adhkarUpdated = 0;
    private int $categorizablesInserted = 0;

    public function handle(): int
    {
        $this->connection = $this->option('connection') ?: config('database.default');

        $filePath = $this->resolveFilePath();
        if ($filePath === '' || ! is_readable($filePath)) {
            $this->error('JSON file path is required and readable. Use --file=/path/to/Adhkar_Categories_Noorly.json');

            return self::FAILURE;
        }

        $json = $this->loadAndValidateJson($filePath);
        if ($json === null) {
            return self::FAILURE;
        }

        $categories = $json['categories'] ?? [];
        $this->jsonCategoryCount = count($categories);
        $this->jsonItemCount = 0;
        foreach ($categories as $c) {
            $this->jsonItemCount += count($c['items'] ?? []);
        }

        $this->dryRunInspect($json);

        $this->printDryRunReport();

        if (! $this->option('execute')) {
            $this->newLine();
            $this->info('This was a DRY RUN. No data was changed.');
            $this->line('To run the import: php artisan adhkar:import-from-json --file='.escapeshellarg($filePath).' --execute');
            return self::SUCCESS;
        }

        return $this->runImport($json);
    }

    private function resolveFilePath(): string
    {
        $path = $this->option('file') ?: trim(config('adhkar_import.json_path', ''));
        return is_string($path) ? $path : '';
    }

    private function loadAndValidateJson(string $filePath): ?array
    {
        $raw = file_get_contents($filePath);
        if ($raw === false) {
            $this->error('Failed to read file.');
            return null;
        }
        $json = json_decode($raw, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('Invalid JSON: '.json_last_error_msg());
            return null;
        }
        if (! isset($json['categories']) || ! is_array($json['categories'])) {
            $this->error('JSON must contain "categories" array.');
            return null;
        }
        return $json;
    }

    /**
     * STEP 0 — Inspect existing data (no writes). Uses loaded $json only.
     */
    private function dryRunInspect(array $json): void
    {
        $conn = $this->connection;

        $this->categoriesScope3 = DB::connection($conn)
            ->table('categories')
            ->where('scope_id', self::DUAS_SCOPE_ID)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $this->categoriesScope4 = DB::connection($conn)
            ->table('categories')
            ->where('scope_id', self::ADHKAR_SCOPE_ID)
            ->orderBy('id')
            ->get()
            ->map(fn ($r) => (array) $r)
            ->all();

        $this->categoryIdsUsedByDua = DB::connection($conn)
            ->table('categorizables')
            ->where('categorizable_type', Dua::class)
            ->distinct()
            ->pluck('category_id')
            ->all();

        $this->categoryIdsUsedByAdhkar = DB::connection($conn)
            ->table('categorizables')
            ->where('categorizable_type', self::ADHKAR_MODEL_CLASS)
            ->distinct()
            ->pluck('category_id')
            ->all();

        $this->jsonCategoryIdToResolvedId = [];
        $this->wouldCreateCategories = 0;
        $this->wouldReuseCategories = 0;
        $this->wouldInsertAdhkar = 0;
        $this->wouldUpdateAdhkar = 0;
        $this->wouldInsertCategorizables = 0;
        $this->wouldSkipConflict = 0;

        $categories = $json['categories'] ?? [];
        if (empty($categories)) {
            return;
        }

        foreach ($categories as $jsonCat) {
            $jsonId = (string) ($jsonCat['id'] ?? '');
            if ($jsonId === '') {
                continue;
            }

            $resolvedId = $this->resolveCategoryIdForJsonCategory($jsonId);
            $this->jsonCategoryIdToResolvedId[$jsonId] = $resolvedId;

            if ($resolvedId === null) {
                $this->wouldCreateCategories++;
            } else {
                $this->wouldReuseCategories++;
            }
        }

        foreach ($categories as $jsonCat) {
            $jsonId = (string) ($jsonCat['id'] ?? '');
            $categoryId = $this->jsonCategoryIdToResolvedId[$jsonId] ?? null;
            $items = $jsonCat['items'] ?? [];
            $position = 0;

            if ($categoryId === null) {
                $this->wouldInsertAdhkar += count($items);
                $this->wouldInsertCategorizables += count($items);
                continue;
            }

            foreach ($items as $item) {
                $position++;
                $existing = Adhkar::on($this->connection)
                    ->where('category_id', $categoryId)
                    ->where('category_key', $jsonId)
                    ->where('position', $position)
                    ->first();

                if ($existing) {
                    $this->wouldUpdateAdhkar++;
                    $existsPivot = DB::connection($this->connection)
                        ->table('categorizables')
                        ->where('category_id', $categoryId)
                        ->where('categorizable_type', self::ADHKAR_MODEL_CLASS)
                        ->where('categorizable_id', $existing->id)
                        ->exists();
                    if (! $existsPivot) {
                        $this->wouldInsertCategorizables++;
                    }
                } else {
                    $this->wouldInsertAdhkar++;
                    $this->wouldInsertCategorizables++;
                }
            }
        }
    }

    /**
     * Resolve category_id for JSON category.id: scope_id=4 AND (translation slug = jsonId or category.slug = jsonId).
     * Returns null if no match (caller will create new category).
     */
    private function resolveCategoryIdForJsonCategory(string $jsonId): ?int
    {
        $conn = $this->connection;

        $byTranslation = DB::connection($conn)
            ->table('category_translations')
            ->where('category_translations.slug', $jsonId)
            ->where('category_translations.language_code', 'en')
            ->join('categories', 'categories.id', '=', 'category_translations.category_id')
            ->where('categories.scope_id', self::ADHKAR_SCOPE_ID)
            ->value('categories.id');

        if ($byTranslation !== null) {
            return (int) $byTranslation;
        }

        $byCategorySlug = DB::connection($conn)
            ->table('categories')
            ->where('scope_id', self::ADHKAR_SCOPE_ID)
            ->where('slug', $jsonId)
            ->value('id');

        if ($byCategorySlug !== null) {
            return (int) $byCategorySlug;
        }

        return null;
    }

    private function printDryRunReport(): void
    {
        $this->info('=== Adhkar import dry-run report ===');
        $this->newLine();

        $conn = $this->connection;

        if (Schema::connection($conn)->hasTable('content_scopes')) {
            $scopes = DB::connection($conn)->table('content_scopes')->select('id', 'key')->orderBy('id')->get();
            $this->info('--- content_scopes ---');
            $this->table(['id', 'key'], $scopes->map(fn ($s) => [$s->id, $s->key]));
            $this->newLine();
        }

        $this->info('--- Categories with scope_id IN (3, 4) ---');
        $rows = [];
        foreach ($this->categoriesScope3 as $r) {
            $id = $r['id'] ?? $r['id'];
            $usedBy = in_array($id, $this->categoryIdsUsedByDua, true) ? 'Dua' : '-';
            $rows[] = [$id, 3, 'duas', $usedBy, 'DO NOT TOUCH'];
        }
        foreach ($this->categoriesScope4 as $r) {
            $id = $r['id'] ?? $r['id'];
            $usedBy = in_array($id, $this->categoryIdsUsedByAdhkar, true) ? 'Adhkar' : '-';
            $rows[] = [$id, 4, 'adhkar', $usedBy, 'candidate for match'];
        }
        $this->table(['category_id', 'scope_id', 'scope_key', 'used_by', 'note'], $rows);
        $this->newLine();

        $this->info('--- Safe Adhkar category matching (scope_id=4, slug=JSON category.id) ---');
        $this->line('Matching strategy: 1) category_translations.slug = JSON category.id AND language_code=en AND categories.scope_id=4; 2) else categories.slug = JSON category.id AND scope_id=4.');
        $this->line('If no match: INSERT new category with scope_id=4 only. Never repurpose scope_id=3.');
        $this->newLine();

        $this->info('--- Summary (dry-run) ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['JSON categories needed', $this->jsonCategoryCount],
                ['JSON adhkar items', $this->jsonItemCount],
                ['Categories with scope_id=3 (duas)', count($this->categoriesScope3)],
                ['Categories with scope_id=4 (adhkar)', count($this->categoriesScope4)],
                ['Category IDs used by Dua (categorizables)', count($this->categoryIdsUsedByDua)],
                ['Category IDs used by Adhkar (categorizables)', count($this->categoryIdsUsedByAdhkar)],
                ['Would create new categories (scope_id=4)', $this->wouldCreateCategories],
                ['Would reuse existing (scope_id=4, slug match)', $this->wouldReuseCategories],
                ['Would insert adhkar', $this->wouldInsertAdhkar],
                ['Would update adhkar (idempotent match)', $this->wouldUpdateAdhkar],
                ['Would insert categorizables', $this->wouldInsertCategorizables],
                ['Skipped (conflicts)', $this->wouldSkipConflict],
            ]
        );

        $this->line('Categorizables: categorizable_type = '.self::ADHKAR_MODEL_CLASS);
    }

    private function runImport(array $json): int
    {
        $categories = $json['categories'] ?? [];
        $this->ensureLanguagesExist();

        $this->categoriesCreated = 0;
        $this->categoriesReused = 0;
        $this->translationsInserted = 0;
        $this->translationsUpdated = 0;
        $this->adhkarInserted = 0;
        $this->adhkarUpdated = 0;
        $this->categorizablesInserted = 0;
        $this->jsonCategoryIdToResolvedId = [];

        try {
            DB::connection($this->connection)->transaction(function () use ($categories) {
                foreach ($categories as $jsonCat) {
                    $jsonId = (string) ($jsonCat['id'] ?? '');
                    if ($jsonId === '') {
                        continue;
                    }
                    $resolved = $this->resolveCategoryIdForJsonCategory($jsonId);
                    if ($resolved === null) {
                        $resolved = $this->createNewAdhkarCategory($jsonCat);
                        $this->categoriesCreated++;
                    } else {
                        $this->categoriesReused++;
                    }
                    $this->jsonCategoryIdToResolvedId[$jsonId] = $resolved;
                }
                foreach ($categories as $jsonCat) {
                    $jsonId = (string) ($jsonCat['id'] ?? '');
                    $categoryId = $this->jsonCategoryIdToResolvedId[$jsonId] ?? null;
                    if ($categoryId === null) {
                        continue;
                    }
                    $this->upsertCategoryTranslations($categoryId, $jsonCat);
                    $this->importAdhkarItemsForCategory($categoryId, $jsonId, $jsonCat['items'] ?? []);
                }
            });
        } catch (\Throwable $e) {
            $this->error('Import failed (rolled back): '.$e->getMessage());
            $this->line('Rollback: transaction was rolled back. No partial writes.');
            return self::FAILURE;
        }

        $this->printFinalReport();
        return self::SUCCESS;
    }

    private function ensureLanguagesExist(): void
    {
        $exists = DB::connection($this->connection)->table('languages')->whereIn('code', ['ar', 'en'])->pluck('code')->all();
        $missing = array_diff(['ar', 'en'], $exists);
        if ($missing !== []) {
            throw new \RuntimeException('languages table must contain ar and en. Missing: '.implode(', ', $missing));
        }
    }

    private function createNewAdhkarCategory(array $jsonCat): int
    {
        $emoji = $jsonCat['emoji'] ?? null;
        $iconKey = is_string($emoji) && mb_strlen($emoji) <= 64 ? $emoji : null;

        $id = DB::connection($this->connection)->table('categories')->insertGetId([
            'scope_id' => self::ADHKAR_SCOPE_ID,
            'icon_key' => $iconKey,
            'icon_color' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return (int) $id;
    }

    private function upsertCategoryTranslations(int $categoryId, array $jsonCat): void
    {
        $jsonId = (string) ($jsonCat['id'] ?? '');
        $nameEn = $jsonCat['name']['en'] ?? $jsonCat['name']['ar'] ?? $jsonId;
        $nameAr = $jsonCat['name']['ar'] ?? $jsonCat['name']['en'] ?? $jsonId;
        $descEn = $jsonCat['description']['en'] ?? null;
        $descAr = $jsonCat['description']['ar'] ?? null;

        foreach (['en' => [$nameEn, $descEn], 'ar' => [$nameAr, $descAr]] as $lang => [$name, $desc]) {
            $t = CategoryTranslation::on($this->connection)
                ->where('category_id', $categoryId)
                ->where('language_code', $lang)
                ->first();

            if ($t === null) {
                CategoryTranslation::on($this->connection)->create([
                    'category_id' => $categoryId,
                    'language_code' => $lang,
                    'name' => $name,
                    'slug' => $jsonId,
                    'description' => $desc,
                ]);
                $this->translationsInserted++;
            } else {
                $t->update(['name' => $name, 'slug' => $jsonId, 'description' => $desc]);
                $this->translationsUpdated++;
            }
        }
    }

    private function importAdhkarItemsForCategory(int $categoryId, string $categoryKey, array $items): void
    {
        $position = 0;
        foreach ($items as $item) {
            $position++;

            $existing = Adhkar::on($this->connection)
                ->where('category_id', $categoryId)
                ->where('category_key', $categoryKey)
                ->where('position', $position)
                ->first();

            $textAr = $item['arabic'] ?? '';
            $textEn = $item['translation'] ?? '';
            $textArNormalized = $textAr !== '' ? ArabicTextNormalizer::normalize($textAr) : null;
            $payload = [
                'category_id' => $categoryId,
                'text' => ['ar' => $textAr, 'en' => $textEn],
                'text_ar_normalized' => $textArNormalized,
                'count' => (int) ($item['repetition'] ?? 1),
                'reward' => [
                    'ar' => $item['benefit']['ar'] ?? '',
                    'en' => $item['benefit']['en'] ?? '',
                ],
                'source' => $item['source']['en'] ?? $item['source']['ar'] ?? null,
                'audio_url' => ! empty($item['audio_url']) ? $item['audio_url'] : null,
                'category_key' => $categoryKey,
                'position' => $position,
                'is_active' => true,
                'is_featured' => false,
            ];

            if ($existing) {
                $existing->update($payload);
                $this->adhkarUpdated++;
                $adhkarId = $existing->id;
            } else {
                $adhkar = Adhkar::on($this->connection)->create($payload);
                $this->adhkarInserted++;
                $adhkarId = $adhkar->id;
            }

            $exists = DB::connection($this->connection)
                ->table('categorizables')
                ->where('category_id', $categoryId)
                ->where('categorizable_type', self::ADHKAR_MODEL_CLASS)
                ->where('categorizable_id', $adhkarId)
                ->exists();

            if (! $exists) {
                DB::connection($this->connection)->table('categorizables')->insert([
                    'category_id' => $categoryId,
                    'categorizable_type' => self::ADHKAR_MODEL_CLASS,
                    'categorizable_id' => $adhkarId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $this->categorizablesInserted++;
            }
        }
    }

    private function printFinalReport(): void
    {
        $this->newLine();
        $this->info('--- Final report ---');
        $this->table(
            ['Metric', 'Value'],
            [
                ['Categories created (scope_id=4)', $this->categoriesCreated],
                ['Categories reused (slug match)', $this->categoriesReused],
                ['Translations inserted', $this->translationsInserted],
                ['Translations updated', $this->translationsUpdated],
                ['Adhkar inserted', $this->adhkarInserted],
                ['Adhkar updated', $this->adhkarUpdated],
                ['Categorizables inserted', $this->categorizablesInserted],
            ]
        );
        $this->line('Rollback: If anything failed, the transaction was rolled back.');
    }
}
