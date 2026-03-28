<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Allow position values beyond TINYINT UNSIGNED (255), matching widened sort_order.
     */
    public function up(): void
    {
        DB::statement('ALTER TABLE journey_week_lessons MODIFY position SMALLINT UNSIGNED NOT NULL DEFAULT 1');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE journey_week_lessons MODIFY position TINYINT UNSIGNED NOT NULL DEFAULT 1');
    }
};
