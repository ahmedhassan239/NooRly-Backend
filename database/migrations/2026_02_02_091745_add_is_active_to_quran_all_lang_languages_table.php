<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if column already exists
        if (Schema::connection('mysql_quran_all_lang')->hasColumn('languages', 'is_active')) {
            return;
        }

        // Use the quran_all_lang connection
        Schema::connection('mysql_quran_all_lang')->table('languages', function (Blueprint $table) {
            $table->boolean('is_active')->default(false)->after('is_rtl');
        });

        // Set all languages to inactive by default
        DB::connection('mysql_quran_all_lang')
            ->table('languages')
            ->update(['is_active' => false]);

        // Set only 'ar' and 'en' to active
        DB::connection('mysql_quran_all_lang')
            ->table('languages')
            ->whereIn('code', ['ar', 'en'])
            ->update(['is_active' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::connection('mysql_quran_all_lang')->hasColumn('languages', 'is_active')) {
            Schema::connection('mysql_quran_all_lang')->table('languages', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
