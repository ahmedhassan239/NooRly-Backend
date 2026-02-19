<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journey_week_lessons', function (Blueprint $table) {
            $table->dropUnique(['journey_week_id', 'day_number']);
        });

        Schema::table('journey_week_lessons', function (Blueprint $table) {
            if (! Schema::hasColumn('journey_week_lessons', 'position')) {
                $table->unsignedTinyInteger('position')->default(1)->after('day_number');
            }
        });

        $this->backfillPositionAndSortOrder();

        Schema::table('journey_week_lessons', function (Blueprint $table) {
            $table->unique(['journey_week_id', 'day_number', 'position'], 'jwl_week_day_position_unique');
            $table->index(['journey_week_id', 'day_number']);
            $table->index(['journey_week_id', 'sort_order']);
        });
    }

    private function backfillPositionAndSortOrder(): void
    {
        $rows = DB::table('journey_week_lessons')->orderBy('journey_week_id')->orderBy('day_number')->orderBy('id')->get();
        $byWeekDay = [];
        foreach ($rows as $row) {
            $key = $row->journey_week_id . '_' . $row->day_number;
            if (! isset($byWeekDay[$key])) {
                $byWeekDay[$key] = 0;
            }
            $byWeekDay[$key]++;
            $position = $byWeekDay[$key];
            $sortOrder = (int) $row->day_number * 100 + $position;
            DB::table('journey_week_lessons')->where('id', $row->id)->update([
                'position' => $position,
                'sort_order' => $sortOrder,
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('journey_week_lessons', function (Blueprint $table) {
            $table->dropUnique('jwl_week_day_position_unique');
        });
        Schema::table('journey_week_lessons', function (Blueprint $table) {
            if (Schema::hasColumn('journey_week_lessons', 'position')) {
                $table->dropColumn('position');
            }
        });
        Schema::table('journey_week_lessons', function (Blueprint $table) {
            $table->unique(['journey_week_id', 'day_number']);
        });
    }
};
