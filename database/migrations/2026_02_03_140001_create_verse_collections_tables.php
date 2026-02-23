<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verse_collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('icon', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index('display_order');
        });

        Schema::create('verse_collection_ayah', function (Blueprint $table) {
            $table->id();
            $table->foreignId('verse_collection_id')->constrained('verse_collections')->cascadeOnDelete();
            $table->unsignedBigInteger('quran_ayah_id');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['verse_collection_id', 'quran_ayah_id']);
        });

        Schema::create('category_verse_coll', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('verse_collection_id')->constrained('verse_collections')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'verse_collection_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('category_verse_coll');
        Schema::dropIfExists('verse_collection_ayah');
        Schema::dropIfExists('verse_collections');
    }
};
