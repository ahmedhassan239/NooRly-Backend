<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('lesson_completions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('lesson_id'); // ID from JSON dataset
            $table->timestamp('completed_at');
            $table->timestamps();

            $table->unique(['app_user_id', 'lesson_id']);
        });

        Schema::create('lesson_reflections', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('lesson_id'); // ID from JSON dataset
            $table->text('reflection_text');
            $table->timestamps();

            $table->unique(['app_user_id', 'lesson_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesson_reflections');
        Schema::dropIfExists('lesson_completions');
    }
};
