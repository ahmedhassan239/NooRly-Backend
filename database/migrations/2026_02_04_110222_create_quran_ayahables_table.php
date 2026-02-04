<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This pivot table links module items (DailyTask, Dua, Lesson) to Quran verses.
     * We store the quran_ayah_id as an integer reference without a foreign key constraint
     * since the verse lives in a different database (mysql_quran_all_lang).
     */
    public function up(): void
    {
        Schema::create('quran_ayahables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quran_ayah_id'); // References quran_all_lang.quran_verses.id
            $table->string('ayahable_type'); // DailyTask, Dua, Lesson, etc.
            $table->unsignedBigInteger('ayahable_id');
            $table->timestamps();

            // Indexes for performance
            $table->index('quran_ayah_id');
            $table->index(['ayahable_type', 'ayahable_id']);
            
            // Unique constraint to prevent duplicates
            $table->unique(['quran_ayah_id', 'ayahable_type', 'ayahable_id'], 'quran_ayahables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quran_ayahables');
    }
};
