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
        Schema::create('dua_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dua_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('title');
            $table->text('translation_text');
            $table->text('transliteration')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
            
            $table->unique(['dua_id', 'language_code']);
            $table->index('language_code');
            $table->index(['language_code', 'title']);
            
            $table->foreign('language_code')
                  ->references('code')
                  ->on('languages')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dua_translations');
    }
};
