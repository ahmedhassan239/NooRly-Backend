<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('hadith_collections', function (Blueprint $table) {
            $table->string('collection_key', 50)->primary();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('hadith_items', function (Blueprint $table) {
            $table->id();
            $table->string('collection_key', 50)->index();
            $table->unsignedInteger('book_number')->index();
            $table->unsignedInteger('hadith_number')->index();
            $table->string('grade')->nullable();
            $table->string('reference')->nullable();
            $table->longText('text_ar')->nullable();
            $table->longText('text_en')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->unique(['collection_key', 'book_number', 'hadith_number'], 'uq_hadith');
            $table->foreign('collection_key')->references('collection_key')->on('hadith_collections')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hadith_items');
        Schema::dropIfExists('hadith_collections');
    }
};
