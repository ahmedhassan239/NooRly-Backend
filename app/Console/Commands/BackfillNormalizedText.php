<?php

namespace App\Console\Commands;

use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillNormalizedText extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'noorly:backfill-normalized-text 
                            {--table= : Specific table to backfill (duas, dua_translations, daily_tasks, lessons, verse_texts, adhkar)}
                            {--batch=500 : Batch size for processing}
                            {--force : Force backfill even if column already has data}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill normalized Arabic text columns for search optimization';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $table = $this->option('table');
        $batchSize = (int) $this->option('batch');
        $force = $this->option('force');

        $tables = $table 
            ? [$table] 
            : ['duas', 'dua_translations', 'daily_tasks', 'lessons', 'verse_texts', 'adhkar'];

        foreach ($tables as $tableName) {
            $this->processTable($tableName, $batchSize, $force);
        }

        $this->info('Backfill completed!');
        return Command::SUCCESS;
    }

    /**
     * Process a single table
     */
    private function processTable(string $table, int $batchSize, bool $force): void
    {
        $config = $this->getTableConfig($table);
        
        if (!$config) {
            $this->warn("Unknown table: {$table}");
            return;
        }

        // Check if table exists
        $connection = $config['connection'] ?? 'mysql';
        if (!Schema::connection($connection)->hasTable($config['table'])) {
            $this->warn("Table {$config['table']} does not exist on connection {$connection}");
            return;
        }

        // Check if normalized column exists
        if (!Schema::connection($connection)->hasColumn($config['table'], $config['normalized_column'])) {
            $this->warn("Column {$config['normalized_column']} does not exist in {$config['table']}");
            return;
        }

        $this->info("Processing {$config['table']}...");

        $query = DB::connection($connection)->table($config['table']);
        
        // Only process rows that need updating (unless force)
        if (!$force) {
            $query->whereNull($config['normalized_column'])
                  ->orWhere($config['normalized_column'], '');
        }

        $total = $query->count();
        
        if ($total === 0) {
            $this->info("  No rows to process in {$config['table']}");
            return;
        }

        $this->info("  Found {$total} rows to process");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $processed = 0;
        
        DB::connection($connection)->table($config['table'])
            ->when(!$force, function ($q) use ($config) {
                $q->whereNull($config['normalized_column'])
                  ->orWhere($config['normalized_column'], '');
            })
            ->orderBy('id')
            ->chunk($batchSize, function ($rows) use ($config, $connection, &$processed, $bar) {
                foreach ($rows as $row) {
                    $sourceText = $row->{$config['source_column']} ?? '';
                    
                    if (!empty($sourceText)) {
                        $normalizedText = ArabicTextNormalizer::normalize($sourceText);
                        
                        DB::connection($connection)
                            ->table($config['table'])
                            ->where('id', $row->id)
                            ->update([
                                $config['normalized_column'] => $normalizedText,
                            ]);
                    }
                    
                    $processed++;
                    $bar->advance();
                }
            });

        $bar->finish();
        $this->newLine();
        $this->info("  Processed {$processed} rows in {$config['table']}");
    }

    /**
     * Get configuration for each table
     */
    private function getTableConfig(string $table): ?array
    {
        $configs = [
            'duas' => [
                'table' => 'duas',
                'source_column' => 'text_ar',
                'normalized_column' => 'text_ar_normalized',
                'connection' => 'mysql',
            ],
            'dua_translations' => [
                'table' => 'dua_translations',
                'source_column' => 'translation_text',
                'normalized_column' => 'text_normalized',
                'connection' => 'mysql',
            ],
            'daily_tasks' => [
                'table' => 'daily_tasks',
                'source_column' => 'description',
                'normalized_column' => 'description_ar_normalized',
                'connection' => 'mysql',
            ],
            'lessons' => [
                'table' => 'lessons',
                'source_column' => 'content',
                'normalized_column' => 'content_ar_normalized',
                'connection' => 'mysql',
            ],
            'verse_texts' => [
                'table' => 'verse_texts',
                'source_column' => 'text',
                'normalized_column' => 'text_normalized',
                'connection' => 'mysql_quran_all_lang',
            ],
            'adhkar' => [
                'table' => 'adhkar',
                'source_column' => 'text', // JSON column - will need special handling
                'normalized_column' => 'text_ar_normalized',
                'connection' => 'mysql',
            ],
        ];

        return $configs[$table] ?? null;
    }
}
