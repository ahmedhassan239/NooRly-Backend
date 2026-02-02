<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This pivot table links categories (local DB) to quran_verses (quran_all_lang DB).
     * We store the verse_id as an integer reference without a foreign key constraint
     * since the verse lives in a different database.
     */
    public function up(): void
    {
        Schema::create('category_verse', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('verse_id'); // References quran_all_lang.quran_verses.id
            $table->timestamps();

            $table->unique(['category_id', 'verse_id']);
            $table->index('verse_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_verse');
    }
};
