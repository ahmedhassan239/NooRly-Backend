<?php

namespace App\Console\Commands;

use App\Domain\Categories\Models\Category;
use App\Domain\Categories\Models\CategoryTranslation;
use App\Domain\Duas\Dua;
use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Idempotent import of Duas_Categories_Noorly.json:
 * A) Import JSON categories into `categories` + `category_translations` (upsert by slug = JSON category id).
 * B) Import JSON items into `duas` (upsert by dua_key).
 * C) Set scope_id = TARGET_SCOPE_ID on all imported categories.
 *
 * Does NOT write to legacy_duas or legacy_dua_translations.
 * categories and duas use the default Laravel DB connection unless overridden.
 */
class ImportDuasFromJsonCommand extends Command
{
    protected $signature = 'duas:import-from-json
                            {--file= : Path to Duas_Categories_Noorly.json (overrides config)}
                            {--target-scope-id= : Scope ID for imported dua categories (overrides config)}
                            {--duas-connection= : DB connection for duas table}
                            {--categories-connection= : DB connection for categories table}
                            {--dry-run : Validate JSON and log what would be done, no DB writes}';

    protected $description = 'Import JSON categories into categories table and JSON items into duas table; set scope_id on imported categories';

    private string $duasConnection;

    private string $categoriesConnection;

    private int $duasInserted = 0;

    private int $duasUpdated = 0;

    private int $categoriesInserted = 0;

    private int $categoriesUpdated = 0;

    /** @var array<int> */
    private array $importedCategoryIds = [];

    private bool $categoryId3ExistedBefore = false;

    private bool $categoryId3InImportedSet = false;

    public function handle(): int
    {
        $filePath = $this->resolveFilePath();
        if ($filePath === '') {
            $this->error('JSON file path is required. Set DUAS_IMPORT_JSON_PATH or use --file=/path/to/Duas_Categories_Noorly.json');

            return self::FAILURE;
        }

        if (! is_readable($filePath)) {
            $this->error("File not readable: {$filePath}");

            return self::FAILURE;
        }

        $json = $this->loadAndValidateJson($filePath);
        if ($json === null) {
            return self::FAILURE;
        }

        $targetScopeId = $this->resolveTargetScopeId();
        if ($targetScopeId === null && ! $this->option('dry-run')) {
            $this->error('Target scope ID is required. Set DUAS_IMPORT_TARGET_SCOPE_ID or use --target-scope-id=<id>');

            return self::FAILURE;
        }

        $this->duasConnection = $this->option('duas-connection') ?: config('duas_import.duas_connection', config('database.default'));
        $this->categoriesConnection = $this->option('categories-connection') ?: config('duas_import.categories_connection', config('database.default'));

        $this->printCategoriesSchema();

        $this->categoryId3ExistedBefore = Category::on($this->categoriesConnection)->where('id', 3)->exists();

        $categories = $json['categories'] ?? [];
        $libraryName = $json['library_name'] ?? 'Duas';

        if (! $this->option('dry-run')) {
            $this->ensureLanguagesExist();
            $this->importCategories($categories, (int) $targetScopeId);
            $this->setScopeIdForImportedCategories((int) $targetScopeId);
            $itemsFound = 0;
            $this->importDuas($categories, $libraryName, $itemsFound);
            Log::info('duas:import-from-json completed', [
                'categories_inserted' => $this->categoriesInserted,
                'categories_updated' => $this->categoriesUpdated,
                'duas_inserted' => $this->duasInserted,
                'duas_updated' => $this->duasUpdated,
                'imported_category_ids' => $this->importedCategoryIds,
            ]);
        } else {
            $itemsFound = 0;
            foreach ($categories as $c) {
                $itemsFound += count($c['items'] ?? []);
            }
            $this->warn('Dry run: no DB writes. Would import '.count($categories).' categories and '.$itemsFound.' duas.');
        }

        $this->printResults($json, $targetScopeId, $filePath);

        return self::SUCCESS;
    }

    private function printCategoriesSchema(): void
    {
        $conn = $this->categoriesConnection;
        if (! Schema::connection($conn)->hasTable('categories')) {
            $this->warn('categories table not found on connection '.$conn);

            return;
        }

        $cols = Schema::connection($conn)->getColumnListing('categories');
        $this->info('--- categories table schema (connection: '.$conn.') ---');
        $this->line('Columns: '.implode(', ', $cols));

        if (Schema::connection($conn)->hasTable('category_translations')) {
            $transCols = Schema::connection($conn)->getColumnListing('category_translations');
            $this->line('category_translations columns: '.implode(', ', $transCols));
        }

        $this->line('Category model fillable: scope_id, icon_key, icon_color');
        $this->line('Stable key for upsert: category_translations.slug with language_code=en = JSON category id (e.g. daily_duas)');
        $this->newLine();
    }

    private function ensureLanguagesExist(): void
    {
        $exists = DB::connection($this->categoriesConnection)
            ->table('languages')
            ->whereIn('code', ['ar', 'en'])
            ->pluck('code')
            ->all();
        $missing = array_diff(['ar', 'en'], $exists);
        if ($missing !== []) {
            throw new \RuntimeException(
                'languages table must contain rows for: ar, en. Missing: '.implode(', ', $missing).'. Run your languages seeder first.'
            );
        }
    }

    /**
     * Import JSON categories into categories + category_translations.
     * Stable key: category_translations (language_code='en', slug = JSON category id).
     */
    private function importCategories(array $jsonCategories, int $targetScopeId): void
    {
        $this->categoriesInserted = 0;
        $this->categoriesUpdated = 0;
        $this->importedCategoryIds = [];

        DB::connection($this->categoriesConnection)->transaction(function () use ($jsonCategories, $targetScopeId) {
            foreach ($jsonCategories as $cat) {
                $jsonId = (string) ($cat['id'] ?? '');
                if ($jsonId === '') {
                    continue;
                }

                $slug = $jsonId;
                $nameEn = $cat['name']['en'] ?? $cat['name']['ar'] ?? $jsonId;
                $nameAr = $cat['name']['ar'] ?? $cat['name']['en'] ?? $jsonId;
                $descEn = $cat['description']['en'] ?? null;
                $descAr = $cat['description']['ar'] ?? null;
                $emoji = $cat['emoji'] ?? null;
                $iconKey = is_string($emoji) && mb_strlen($emoji) <= 64 ? $emoji : null;

                $transEn = CategoryTranslation::on($this->categoriesConnection)
                    ->where('language_code', 'en')
                    ->where('slug', $slug)
                    ->first();

                if ($transEn !== null) {
                    $category = Category::on($this->categoriesConnection)->find($transEn->category_id);
                    if ($category !== null) {
                        $category->update([
                            'scope_id' => $targetScopeId,
                            'icon_key' => $iconKey,
                            'icon_color' => null,
                        ]);
                        $this->upsertCategoryTranslation($transEn->category_id, 'en', $slug, $nameEn, $descEn);
                        $this->upsertCategoryTranslation($transEn->category_id, 'ar', $slug, $nameAr, $descAr);
                        $this->categoriesUpdated++;
                        $this->importedCategoryIds[] = $category->id;
                    }
                } else {
                    $category = Category::on($this->categoriesConnection)->create([
                        'scope_id' => $targetScopeId,
                        'icon_key' => $iconKey,
                        'icon_color' => null,
                    ]);
                    CategoryTranslation::on($this->categoriesConnection)->create([
                        'category_id' => $category->id,
                        'language_code' => 'en',
                        'name' => $nameEn,
                        'slug' => $slug,
                        'description' => $descEn,
                    ]);
                    CategoryTranslation::on($this->categoriesConnection)->create([
                        'category_id' => $category->id,
                        'language_code' => 'ar',
                        'name' => $nameAr,
                        'slug' => $slug,
                        'description' => $descAr,
                    ]);
                    $this->categoriesInserted++;
                    $this->importedCategoryIds[] = $category->id;
                }
            }
        });
    }

    private function upsertCategoryTranslation(int $categoryId, string $lang, string $slug, string $name, ?string $description): void
    {
        $t = CategoryTranslation::on($this->categoriesConnection)
            ->where('category_id', $categoryId)
            ->where('language_code', $lang)
            ->first();

        if ($t !== null) {
            $t->update(['name' => $name, 'slug' => $slug, 'description' => $description]);
        } else {
            CategoryTranslation::on($this->categoriesConnection)->create([
                'category_id' => $categoryId,
                'language_code' => $lang,
                'name' => $name,
                'slug' => $slug,
                'description' => $description,
            ]);
        }
    }

    private function setScopeIdForImportedCategories(int $targetScopeId): void
    {
        if ($this->importedCategoryIds === []) {
            return;
        }

        Category::on($this->categoriesConnection)
            ->whereIn('id', $this->importedCategoryIds)
            ->update(['scope_id' => $targetScopeId]);
    }

    private function importDuas(array $categories, string $libraryName, int &$itemsFound): void
    {
        $this->duasInserted = 0;
        $this->duasUpdated = 0;
        $itemsFound = 0;

        DB::connection($this->duasConnection)->transaction(function () use ($categories, $libraryName, &$itemsFound) {
            foreach ($categories as $category) {
                $categoryKey = (string) ($category['id'] ?? '');
                $categoryMeta = $this->buildCategoryMeta($category);
                $items = $category['items'] ?? [];
                $position = 1;

                foreach ($items as $item) {
                    $itemsFound++;
                    $duaKey = (string) ($item['id'] ?? '');
                    if ($duaKey === '') {
                        continue;
                    }

                    $existing = Dua::on($this->duasConnection)->where('dua_key', $duaKey)->first();
                    $payload = $this->buildDuaPayload($item, $categoryKey, $categoryMeta, $libraryName, $position);

                    Dua::on($this->duasConnection)->updateOrCreate(
                        ['dua_key' => $duaKey],
                        $payload
                    );

                    if ($existing) {
                        $this->duasUpdated++;
                    } else {
                        $this->duasInserted++;
                    }
                    $position++;
                }
            }
        });
    }

    private function buildCategoryMeta(array $category): array
    {
        $name = $category['name'] ?? [];
        $desc = $category['description'] ?? [];

        return [
            'id' => $category['id'] ?? null,
            'name' => $name,
            'description' => $desc,
            'emoji' => $category['emoji'] ?? null,
            'total_items' => (int) ($category['total_items'] ?? 0),
        ];
    }

    private function buildDuaPayload(array $item, string $categoryKey, array $categoryMeta, string $libraryName, int $position): array
    {
        $sourceAr = $item['source']['ar'] ?? null;
        $sourceEn = $item['source']['en'] ?? null;
        $source = $sourceAr ?? $sourceEn ?? null;

        $textAr = $item['arabic'] ?? '';
        $textArNormalized = $textAr !== '' ? ArabicTextNormalizer::normalize($textAr) : null;

        $title = $item['title'] ?? [];
        $when = $item['when'] ?? [];
        $benefit = $item['benefit'] ?? [];
        $sourceFull = $item['source'] ?? [];
        $tips = $item['tips'] ?? null;

        $meta = [
            'title' => $title,
            'when' => $when,
            'benefit' => $benefit,
            'source_full' => $sourceFull,
            'audio_url' => $item['audio_url'] ?? null,
            'repetition' => isset($item['repetition']) ? (int) $item['repetition'] : null,
            'difficulty' => $item['difficulty'] ?? null,
            'time_seconds' => isset($item['time_seconds']) ? (int) $item['time_seconds'] : null,
            'tips' => $tips,
            'category' => $categoryMeta,
            'library_name' => $libraryName,
            'legacy_reference' => [
                'legacy_tables_reviewed' => true,
                'import_target' => 'duas',
                'legacy_target_skipped' => true,
            ],
        ];

        return [
            'category_key' => $categoryKey,
            'source' => is_string($source) ? $source : null,
            'text_ar' => $textAr,
            'text_ar_normalized' => $textArNormalized,
            'transliteration' => null,
            'text_en' => $item['translation'] ?? null,
            'meta' => $meta,
            'is_active' => 1,
            'is_featured' => 0,
            'position' => $position,
        ];
    }

    private function printResults(array $json, ?int $targetScopeId, string $filePath): void
    {
        $categories = $json['categories'] ?? [];
        $itemsFound = 0;
        foreach ($categories as $c) {
            $itemsFound += count($c['items'] ?? []);
        }

        $this->categoryId3InImportedSet = in_array(3, $this->importedCategoryIds, true);

        $this->table(
            ['Metric', 'Value'],
            [
                ['Categories (JSON) found', count($categories)],
                ['Categories inserted', $this->categoriesInserted],
                ['Categories updated', $this->categoriesUpdated],
                ['Duas (items) found', $itemsFound],
                ['Duas inserted', $this->duasInserted],
                ['Duas updated', $this->duasUpdated],
                ['Imported category IDs (scope_id set)', implode(', ', $this->importedCategoryIds) ?: 'none'],
                ['categories.id=3 existed before', $this->categoryId3ExistedBefore ? 'yes' : 'no'],
                ['categories.id=3 in imported set', $this->categoryId3InImportedSet ? 'yes' : 'no'],
            ]
        );

        $this->newLine();
        $this->info('--- Category mapping ---');
        $this->line('categories table: id, scope_id, icon_key, icon_color (name/slug/description nullable legacy).');
        $this->line('category_translations: category_id, language_code, name, slug, description.');
        $this->line('Stable key: category_translations.slug (language_code=en) = JSON category id (daily_duas, protection_duas, etc.).');
        $this->line('JSON → categories: scope_id=TARGET_SCOPE_ID, icon_key=emoji; name/slug/description in category_translations (ar, en).');
        $this->newLine();

        $this->info('--- Why previous attempt showed "categories.id=3 scope_id updated | no" ---');
        $this->line('The previous command only ran UPDATE categories SET scope_id=? WHERE id=3.');
        $this->line('It did NOT insert the JSON categories. So either: (1) no row with id=3 existed, or (2) id=3 is an existing category from another scope (e.g. Hadith/Verses), not from this JSON.');
        $this->line('This command now INSERTS/UPSERTS categories from the JSON (by slug=json id) and sets scope_id on those imported category IDs. We do not assume id=3 is the dua category.');
        $this->newLine();

        $this->line('Exact command to run:');
        $this->line('  php artisan duas:import-from-json --file='.escapeshellarg($filePath).' --target-scope-id='.($targetScopeId ?? 'TARGET_SCOPE_ID'));
    }

    private function resolveFilePath(): string
    {
        $option = $this->option('file');
        if ($option !== null && $option !== '') {
            return $option;
        }
        $path = config('duas_import.json_path', '');

        return is_string($path) ? trim($path) : '';
    }

    private function resolveTargetScopeId(): ?int
    {
        $option = $this->option('target-scope-id');
        if ($option !== null && $option !== '') {
            return (int) $option;
        }
        $id = config('duas_import.target_scope_id');

        return $id !== null && $id !== '' ? (int) $id : null;
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

        foreach ($json['categories'] as $index => $cat) {
            if (empty($cat['id']) || ! isset($cat['items']) || ! is_array($cat['items'])) {
                $this->error("Category at index {$index} must have 'id' and 'items' array.");

                return null;
            }
            foreach ($cat['items'] as $itemIndex => $item) {
                if (empty($item['id'])) {
                    $this->error("Item at category {$index}, item {$itemIndex} missing 'id'.");

                    return null;
                }
            }
        }

        return $json;
    }
}
