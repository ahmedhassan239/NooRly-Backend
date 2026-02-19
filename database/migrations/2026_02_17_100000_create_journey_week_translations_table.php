<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_week_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_week_id')->constrained('journey_weeks')->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('title');
            $table->text('description')->nullable();
            $table->timestamps();

            $table->unique(['journey_week_id', 'language_code']);
            $table->index('language_code');
        });

        if (Schema::hasColumn('journey_weeks', 'title')) {
            $weeks = \Illuminate\Support\Facades\DB::table('journey_weeks')->orderBy('id')->get();
            foreach ($weeks as $week) {
                \Illuminate\Support\Facades\DB::table('journey_week_translations')->insert([
                    'journey_week_id' => $week->id,
                    'language_code' => 'en',
                    'title' => $week->title ?? 'Week ' . $week->week_number,
                    'description' => $week->description ?? null,
                    'created_at' => $week->created_at ?? now(),
                    'updated_at' => $week->updated_at ?? now(),
                ]);
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_week_translations');
    }
};
