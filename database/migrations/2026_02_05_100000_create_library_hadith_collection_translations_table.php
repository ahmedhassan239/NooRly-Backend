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
        Schema::create('library_hadith_collection_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('hadith_collection_id');
            $table->foreign('hadith_collection_id', 'lib_hadith_coll_trans_coll_id_fk')
                ->references('id')
                ->on('library_hadith_collections')
                ->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->unique(['hadith_collection_id', 'locale'], 'lib_hadith_coll_trans_coll_locale_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('library_hadith_collection_translations');
    }
};
