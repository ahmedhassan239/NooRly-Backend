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
        Schema::create('category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('name');
            $table->string('slug');
            $table->text('description')->nullable();
            $table->timestamps();

            // Unique constraint: one translation per category per language
            $table->unique(['category_id', 'language_code']);
            
            // Unique slug per language (allows same slug in different languages)
            $table->unique(['language_code', 'slug']);
            
            // Indexes for search
            $table->index('language_code');
            $table->index(['language_code', 'name']);
            $table->index(['language_code', 'slug']);
            
            // Foreign key to languages table
            $table->foreign('language_code')
                  ->references('code')
                  ->on('languages')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('category_translations');
    }
};
