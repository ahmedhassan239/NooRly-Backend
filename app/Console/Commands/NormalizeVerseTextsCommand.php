<?php

namespace App\Console\Commands;

use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Backfill command to populate text_normalized column in verse_texts table.
 * 
 * This command normalizes Arabic text by removing diacritics (tashkeel),
 * tatweel, and standardizing character variants for consistent search.
 * 
 * USAGE:
 *   php artisan quran:normalize-verse-texts           # Process all rows
 *   php artisan quran:normalize-verse-texts --dry-run # Preview without changes
 *   php artisan quran:normalize-verse-texts --force   # Re-normalize existing values
 * 
 * FEATURES:
 * - Memory-safe chunked processing (1000 rows at a time)
 * - Idempotent: skips already-normalized rows by default
 * - Progress bar with ETA
 * - Dry-run mode for preview
 * - Force mode to re-normalize all rows
 */
class NormalizeVerseTextsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quran:normalize-verse-texts
                            {--dry-run : Preview changes without applying them}
                            {--force : Re-normalize all rows, even if already normalized}
                            {--chunk-size=1000 : Number of rows to process at a time}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill text_normalized column in verse_texts for diacritic-agnostic Arabic search';

    /**
     * Database connection name.
     */
    private const CONNECTION = 'mysql_quran_all_lang';

    /**
     * Table name.
     */
    private const TABLE = 'verse_texts';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $force = $this->option('force');
        $chunkSize = (int) $this->option('chunk-size');

        if ($dryRun) {
            $this->warn('🔍 DRY RUN MODE - No changes will be made');
        }

        $this->info('Starting verse text normalization...');
        $this->newLine();

        // Get connection
        $connection = DB::connection(self::CONNECTION);

        // Check if column exists
        if (!$this->columnExists($connection)) {
            $this->error('Column text_normalized does not exist in verse_texts table.');
            $this->error('Please run migrations first: php artisan migrate');
            return Command::FAILURE;
        }

        // Count rows to process
        $query = $connection->table(self::TABLE);
        if (!$force) {
            // Only process rows where text_normalized is NULL
            $query->whereNull('text_normalized');
        }
        $totalRows = $query->count();

        if ($totalRows === 0) {
            $this->info('✅ No rows to process. All verse texts are already normalized.');
            return Command::SUCCESS;
        }

        $this->info("Found {$totalRows} rows to process");
        $this->newLine();

        // Progress bar
        $progressBar = $this->output->createProgressBar($totalRows);
        $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% - %elapsed:6s%/%estimated:-6s% - %memory:6s%');
        $progressBar->start();

        $processed = 0;
        $errors = 0;
        $samples = [];

        // Process in chunks
        $connection->table(self::TABLE)
            ->when(!$force, fn ($q) => $q->whereNull('text_normalized'))
            ->orderBy('id')
            ->chunk($chunkSize, function ($rows) use ($connection, $dryRun, &$processed, &$errors, &$samples, $progressBar) {
                $updates = [];

                foreach ($rows as $row) {
                    try {
                        $normalizedText = ArabicTextNormalizer::normalize($row->text);
                        
                        $updates[] = [
                            'id' => $row->id,
                            'text_normalized' => $normalizedText,
                        ];

                        // Collect some samples for preview
                        if (count($samples) < 5 && $row->text !== $normalizedText) {
                            $samples[] = [
                                'id' => $row->id,
                                'original' => mb_substr($row->text, 0, 60),
                                'normalized' => mb_substr($normalizedText, 0, 60),
                            ];
                        }

                        $processed++;
                    } catch (\Exception $e) {
                        $errors++;
                    }

                    $progressBar->advance();
                }

                // Batch update (not in dry-run mode)
                if (!$dryRun && !empty($updates)) {
                    foreach ($updates as $update) {
                        $connection->table(self::TABLE)
                            ->where('id', $update['id'])
                            ->update(['text_normalized' => $update['text_normalized']]);
                    }
                }
            });

        $progressBar->finish();
        $this->newLine(2);

        // Show samples
        if (!empty($samples)) {
            $this->info('📝 Sample normalizations:');
            $this->table(
                ['ID', 'Original (first 60 chars)', 'Normalized (first 60 chars)'],
                array_map(fn ($s) => [$s['id'], $s['original'], $s['normalized']], $samples)
            );
            $this->newLine();
        }

        // Summary
        $this->info('📊 Summary:');
        $this->line("   Processed: {$processed}");
        if ($errors > 0) {
            $this->warn("   Errors: {$errors}");
        }

        if ($dryRun) {
            $this->newLine();
            $this->warn('🔍 DRY RUN completed - no changes were made');
            $this->info('   Run without --dry-run to apply changes');
        } else {
            $this->newLine();
            $this->info('✅ Normalization completed successfully!');
        }

        return $errors > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    /**
     * Check if the text_normalized column exists.
     */
    private function columnExists($connection): bool
    {
        $columns = $connection->select("DESCRIBE " . self::TABLE);
        foreach ($columns as $column) {
            if ($column->Field === 'text_normalized') {
                return true;
            }
        }
        return false;
    }
}
