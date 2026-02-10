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
        Schema::create('adhkar', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->json('title'); // translatable: {"en": "Morning Dhikr", "ar": "ذكر الصباح"}
            $table->json('text'); // translatable: the actual dhikr text
            $table->string('text_ar_normalized', 1000)->nullable()->index(); // for Arabic search without diacritics
            $table->integer('count')->default(1); // how many times to repeat
            $table->json('reward')->nullable(); // translatable: reward/benefit
            $table->string('source', 255)->nullable(); // hadith source reference
            $table->string('audio_url', 500)->nullable();
            $table->string('category_key', 100)->nullable()->index(); // morning, evening, sleep, etc.
            $table->integer('position')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->timestamps();
        });

        // Add full-text index for Arabic search
        \DB::statement('ALTER TABLE adhkar ADD FULLTEXT INDEX adhkar_text_normalized_fulltext (text_ar_normalized)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('adhkar');
    }
};
