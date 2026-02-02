<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add text_normalized column to verse_texts table for diacritic-agnostic Arabic search.
 * 
 * PURPOSE:
 * Arabic text in Quran often contains diacritics (tashkeel) like فتحة، ضمة، كسرة.
 * Example: "بَقَرَةً" vs "بقرة"
 * 
 * When users search without diacritics, we need to match the stored text which has them.
 * The text_normalized column stores pre-normalized Arabic text for efficient searching.
 * 
 * NORMALIZATION INCLUDES:
 * - Removing all harakat/tashkeel (Unicode 064B-065F, 0670, 06D6-06ED)
 * - Removing tatweel/kashida (Unicode 0640)
 * - Normalizing Alef variants (أ/إ/آ/ٱ → ا)
 * - Normalizing Yaa variants (ى → ي)
 * - Normalizing Taa Marbuta (ة → ه)
 * 
 * INDEXING STRATEGY:
 * - FULLTEXT index on text_normalized for fast Arabic word search
 * - FULLTEXT works well with Arabic ngrams in MySQL 5.7+/8.0+ with ngram parser
 * - For LIKE %term% queries, FULLTEXT won't help but the column is still useful
 *   for normalized comparison
 * 
 * BACKFILL:
 * Run: php artisan quran:normalize-verse-texts
 */
return new class extends Migration
{
    /**
     * The database connection to use.
     */
    protected $connection = 'mysql_quran_all_lang';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::connection($this->connection)->table('verse_texts', function (Blueprint $table) {
            // Add normalized text column after the text column
            // LONGTEXT to match the original text column type
            $table->longText('text_normalized')->nullable()->after('text');
        });

        // Add FULLTEXT index for Arabic search
        // Note: FULLTEXT with ngram parser works better for Chinese/Japanese/Korean
        // For Arabic, standard FULLTEXT works but has limitations with short words
        // The main benefit here is the pre-normalized data for LIKE queries
        Schema::connection($this->connection)->table('verse_texts', function (Blueprint $table) {
            $table->fullText('text_normalized', 'verse_texts_text_normalized_fulltext');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::connection($this->connection)->table('verse_texts', function (Blueprint $table) {
            $table->dropFullText('verse_texts_text_normalized_fulltext');
            $table->dropColumn('text_normalized');
        });
    }
};
