<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Expand item_type ENUM to include 'verse' and 'adhkar'.
     * MySQL: MODIFY COLUMN ENUM. SQLite: column is TEXT; no change needed for testing.
     */
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE `saved_items` MODIFY COLUMN `item_type` ENUM('dua','hadith','lesson','verse','adhkar') NOT NULL");
    }

    /**
     * Revert to original 3-value ENUM (MySQL only).
     */
    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if ($driver !== 'mysql') {
            return;
        }
        DB::statement("ALTER TABLE `saved_items` MODIFY COLUMN `item_type` ENUM('dua','hadith','lesson') NOT NULL");
    }
};
