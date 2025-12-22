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
        Schema::create('lesson_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lesson_id')->constrained()->cascadeOnDelete();
            $table->string('language_code', 10);
            $table->string('title');
            $table->text('short_description')->nullable();
            $table->longText('content');
            $table->timestamps();
            
            $table->unique(['lesson_id', 'language_code']);
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
        Schema::dropIfExists('lesson_translations');
    }
};
