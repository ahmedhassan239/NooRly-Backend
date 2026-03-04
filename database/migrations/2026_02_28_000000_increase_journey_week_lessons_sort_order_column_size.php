<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('ALTER TABLE journey_week_lessons MODIFY sort_order SMALLINT UNSIGNED NOT NULL');
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'mysql') {
            return;
        }
        DB::statement('ALTER TABLE journey_week_lessons MODIFY sort_order TINYINT UNSIGNED NOT NULL');
    }
};
