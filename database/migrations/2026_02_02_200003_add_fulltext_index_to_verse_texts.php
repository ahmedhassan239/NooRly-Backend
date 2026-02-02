<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The database connection to use.
     */
    protected $connection = 'mysql_quran_all_lang';

    /**
     * Run the migrations.
     * 
     * Adds FULLTEXT index to verse_texts.text for fast Arabic text search.
     * FULLTEXT indexes work well with Arabic text in MySQL 8.0+ with proper
     * character set (utf8mb4).
     */
    public function up(): void
    {
        // Check if index already exists before adding
        $indexExists = DB::connection($this->connection)
            ->select("SHOW INDEX FROM verse_texts WHERE Key_name = 'verse_texts_text_fulltext'");
        
        if (empty($indexExists)) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE verse_texts ADD FULLTEXT INDEX verse_texts_text_fulltext (text)'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $indexExists = DB::connection($this->connection)
            ->select("SHOW INDEX FROM verse_texts WHERE Key_name = 'verse_texts_text_fulltext'");
        
        if (!empty($indexExists)) {
            DB::connection($this->connection)->statement(
                'ALTER TABLE verse_texts DROP INDEX verse_texts_text_fulltext'
            );
        }
    }
};
