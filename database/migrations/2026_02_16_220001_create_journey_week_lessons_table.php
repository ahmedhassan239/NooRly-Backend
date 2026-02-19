<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journey_week_lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journey_week_id')->constrained('journey_weeks')->cascadeOnDelete();
            $table->foreignId('lesson_id')->constrained('lessons')->cascadeOnDelete();
            $table->unsignedTinyInteger('day_number'); // 1..7
            $table->unsignedTinyInteger('sort_order');
            $table->timestamps();

            $table->unique(['journey_week_id', 'day_number']);
            $table->unique(['journey_week_id', 'lesson_id']);
            $table->index('journey_week_id');
            $table->index('lesson_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journey_week_lessons');
    }
};
