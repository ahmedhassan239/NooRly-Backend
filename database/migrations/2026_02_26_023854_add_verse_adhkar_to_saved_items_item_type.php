<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Expand item_type ENUM to include 'verse' and 'adhkar'.
     * Original ENUM: ('dua','hadith','lesson')
     * New ENUM:      ('dua','hadith','lesson','verse','adhkar')
     */
    public function up(): void
    {
        DB::statement("ALTER TABLE `saved_items` MODIFY COLUMN `item_type` ENUM('dua','hadith','lesson','verse','adhkar') NOT NULL");
    }

    /**
     * Revert to original 3-value ENUM (will truncate any verse/adhkar rows).
     */
    public function down(): void
    {
        DB::statement("ALTER TABLE `saved_items` MODIFY COLUMN `item_type` ENUM('dua','hadith','lesson') NOT NULL");
    }
};
