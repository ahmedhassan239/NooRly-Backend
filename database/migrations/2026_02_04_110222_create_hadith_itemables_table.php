<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Note: This pivot table links module items (DailyTask, Dua, Lesson) to Hadith items.
     * We store the hadith_item_id as an integer reference without a foreign key constraint
     * since the hadith lives in a different database (mysql_hadith).
     */
    public function up(): void
    {
        Schema::create('hadith_itemables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hadith_item_id'); // References hadith DB
            $table->string('hadithable_type'); // DailyTask, Dua, Lesson, etc.
            $table->unsignedBigInteger('hadithable_id');
            $table->timestamps();

            // Indexes for performance
            $table->index('hadith_item_id');
            $table->index(['hadithable_type', 'hadithable_id']);
            
            // Unique constraint to prevent duplicates
            $table->unique(['hadith_item_id', 'hadithable_type', 'hadithable_id'], 'hadith_itemables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hadith_itemables');
    }
};
