<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ContentSchemaScanCommand extends Command
{
    protected $signature = 'content:schema-scan';
    protected $description = 'Scan external databases and map tables/columns';

    public function handle()
    {
        $this->info('Scanning external schemas...');
        
        $quranMap = $this->scanDatabase('mysql_quran', 'quran');
        $hadithMap = $this->scanDatabase('mysql_hadith', 'hadith');

        $this->generateConfig($quranMap, $hadithMap);
    }

    private function scanDatabase($connection, $type)
    {
        $this->comment("Scanning {$connection}...");
        
        try {
            $schemaBuilder = DB::connection($connection)->getSchemaBuilder();
            $tables = $schemaBuilder->getTableListing();
            
            $report = [];
            $bestTable = null;
            $bestScore = -1;

            $targetDb = DB::connection($connection)->getDatabaseName();

            foreach ($tables as $table) {
                // Handle qualified names (db.table) logic
                if (str_contains($table, '.')) {
                    [$db, $tblName] = explode('.', $table, 2);
                    if ($db !== $targetDb) {
                        continue;
                    }
                    $checkTable = $tblName; // Use the short name for scoring/column check
                } else {
                    $checkTable = $table;
                }

                // Skip migrations and system tables
                if (in_array($checkTable, ['migrations', 'password_resets', 'failed_jobs', 'users', 'personal_access_tokens'])) {
                    continue;
                }

                // Get columns using the ORIGINAL full name ($table) so Schema builder finds it
                $columns = Schema::connection($connection)->getColumnListing($table);
                $score = $this->scoreTable($type, $checkTable, $columns);
                
                // Store using full table name to be safe in config
                $report[$table] = [
                    'columns' => $columns,
                    'score' => $score['score'],
                    'matches' => $score['matches'],
                ];

                if ($score['score'] > $bestScore) {
                    $bestScore = $score['score'];
                    $bestTable = [
                        'table' => $table, // Keep full name if needed, or short name if consistent
                        'mapping' => $score['matches']
                    ];
                }
            }

            // Save JSON Report
            $path = storage_path("app/schema/{$type}.schema.json");
            File::ensureDirectoryExists(dirname($path));
            File::put($path, json_encode($report, JSON_PRETTY_PRINT));
            $this->info("Saved schema report to {$path}");

            if ($bestTable) {
                $this->info("Best match for {$type}: {$bestTable['table']} (Score: {$bestScore})");
                return $bestTable;
            } else {
                $this->warn("No suitable table found for {$type}");
                return null;
            }

        } catch (\Exception $e) {
            $this->error("Scan failed for {$connection}: " . $e->getMessage());
            return null;
        }
    }

    private function scoreTable($type, $table, $columns)
    {
        $score = 0;
        $matches = [];
        
        $criteria = $type === 'quran' ? [
            'surah_number' => ['surah', 'surah_number', 'sura', 'surah_id'],
            'ayah_number' => ['ayah', 'ayah_number', 'aya', 'number', 'number_in_surah'],
            'text_ar' => ['text_ar', 'arabic', 'text', 'content', 'ayah_text'],
            'text_en' => ['text_en', 'english', 'translation', 'en_text'],
        ] : [
            'collection' => ['collection', 'collection_id', 'source', 'book_name'],
            'book_number' => ['book', 'book_number', 'chapter_no', 'kitab'],
            'hadith_number' => ['hadith', 'hadith_number', 'hadith_no', 'number'],
            'text_ar' => ['text_ar', 'arabic', 'text', 'matn'],
            'text_en' => ['text_en', 'english', 'translation'],
        ];

        foreach ($criteria as $key => $keywords) {
            foreach ($columns as $col) {
                if (in_array(strtolower($col), $keywords)) {
                    $score++;
                    $matches[$key] = $col;
                    break; 
                }
            }
        }
        
        // Bonus for table name match
        if ($type === 'quran' && in_array($table, ['ayahs', 'quran', 'quran_text'])) $score += 2;
        if ($type === 'hadith' && in_array($table, ['hadiths', 'all_hadiths', 'hadith_collection'])) $score += 2;

        return ['score' => $score, 'matches' => $matches];
    }

    private function generateConfig($quranMap, $hadithMap)
    {
        $configContent = "<?php\n\nreturn [\n";
        
        if ($quranMap) {
            $configContent .= "    'quran' => [\n";
            $configContent .= "        'connection' => 'mysql_quran',\n";
            $configContent .= "        'table' => '{$quranMap['table']}',\n";
            $configContent .= "        'columns' => [\n";
            foreach ($quranMap['mapping'] as $key => $col) {
                $configContent .= "            '{$key}' => '{$col}',\n";
            }
            $configContent .= "        ],\n";
            $configContent .= "    ],\n";
        }

        if ($hadithMap) {
            $configContent .= "    'hadith' => [\n";
            $configContent .= "        'connection' => 'mysql_hadith',\n";
            $configContent .= "        'table' => '{$hadithMap['table']}',\n";
            $configContent .= "        'columns' => [\n";
            foreach ($hadithMap['mapping'] as $key => $col) {
                $configContent .= "            '{$key}' => '{$col}',\n";
            }
            $configContent .= "        ],\n";
            $configContent .= "    ],\n";
        }
        
        $configContent .= "];\n";

        File::put(config_path('content_sources.php'), $configContent);
        $this->info("Generated config/content_sources.php");
    }
}
