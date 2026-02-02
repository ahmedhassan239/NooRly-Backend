<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This pivot table links categories (local DB) to hadiths (hadith DB).
     * We store the hadith_id as an integer reference without a foreign key constraint
     * since the hadith lives in a different database.
     */
    public function up(): void
    {
        Schema::create('category_hadith', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('hadith_id'); // References hadith DB
            $table->timestamps();

            $table->unique(['category_id', 'hadith_id']);
            $table->index('hadith_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_hadith');
    }
};
