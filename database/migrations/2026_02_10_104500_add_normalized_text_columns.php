<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Adds normalized text columns for Arabic search (diacritics-free).
     * These columns allow searching "بقرة" to match "بَقَرَةً" (with tashkeel).
     */
    public function up(): void
    {
        // Add to duas table
        if (Schema::hasTable('duas') && !Schema::hasColumn('duas', 'text_ar_normalized')) {
            Schema::table('duas', function (Blueprint $table) {
                $table->text('text_ar_normalized')->nullable()->after('text_ar');
                if (\DB::getDriverName() !== 'sqlite') {
                    $table->fullText('text_ar_normalized', 'duas_text_ar_normalized_fulltext');
                }
            });
        }

        // Add to dua_translations table
        if (Schema::hasTable('dua_translations') && !Schema::hasColumn('dua_translations', 'text_normalized')) {
            Schema::table('dua_translations', function (Blueprint $table) {
                $table->text('text_normalized')->nullable()->after('translation_text');
            });
        }

        // Add to daily_tasks table
        if (Schema::hasTable('daily_tasks') && !Schema::hasColumn('daily_tasks', 'description_ar_normalized')) {
            Schema::table('daily_tasks', function (Blueprint $table) {
                $table->text('description_ar_normalized')->nullable();
            });
        }

        // Add to lessons table
        if (Schema::hasTable('lessons') && !Schema::hasColumn('lessons', 'content_ar_normalized')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->text('content_ar_normalized')->nullable();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('duas') && Schema::hasColumn('duas', 'text_ar_normalized')) {
            Schema::table('duas', function (Blueprint $table) {
                $table->dropFullText('duas_text_ar_normalized_fulltext');
                $table->dropColumn('text_ar_normalized');
            });
        }

        if (Schema::hasTable('dua_translations') && Schema::hasColumn('dua_translations', 'text_normalized')) {
            Schema::table('dua_translations', function (Blueprint $table) {
                $table->dropColumn('text_normalized');
            });
        }

        if (Schema::hasTable('daily_tasks') && Schema::hasColumn('daily_tasks', 'description_ar_normalized')) {
            Schema::table('daily_tasks', function (Blueprint $table) {
                $table->dropColumn('description_ar_normalized');
            });
        }

        if (Schema::hasTable('lessons') && Schema::hasColumn('lessons', 'content_ar_normalized')) {
            Schema::table('lessons', function (Blueprint $table) {
                $table->dropColumn('content_ar_normalized');
            });
        }
    }
};
