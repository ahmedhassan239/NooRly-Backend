<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('library_hadith_collections')) {
            Schema::create('library_hadith_collections', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('slug')->nullable()->unique();
            $table->string('icon', 64)->nullable();
            $table->string('color', 32)->nullable();
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->index('display_order');
            });
        }

        if (!Schema::hasTable('lib_hadith_collection_item')) {
            Schema::create('lib_hadith_collection_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hadith_collection_id')->constrained('library_hadith_collections')->cascadeOnDelete();
            $table->unsignedBigInteger('hadith_item_id');
            $table->unsignedInteger('display_order')->default(0);
            $table->timestamps();
            $table->unique(['hadith_collection_id', 'hadith_item_id'], 'lib_hadith_coll_item_uq');
            });
        }

        if (!Schema::hasTable('category_lib_hadith_collection')) {
            Schema::create('category_lib_hadith_collection', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('categories')->cascadeOnDelete();
            $table->foreignId('hadith_collection_id')->constrained('library_hadith_collections')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['category_id', 'hadith_collection_id'], 'cat_lib_hadith_uq');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('category_lib_hadith_collection');
        Schema::dropIfExists('lib_hadith_collection_item');
        Schema::dropIfExists('library_hadith_collections');
    }
};
