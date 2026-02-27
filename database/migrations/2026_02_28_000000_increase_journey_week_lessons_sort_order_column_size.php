<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE journey_week_lessons MODIFY sort_order SMALLINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE journey_week_lessons MODIFY sort_order TINYINT UNSIGNED NOT NULL');
    }
};
