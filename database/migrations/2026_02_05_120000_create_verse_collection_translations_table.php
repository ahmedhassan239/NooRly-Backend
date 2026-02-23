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
        Schema::create('verse_collection_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('verse_collection_id');
            $table->foreign('verse_collection_id', 'verse_coll_trans_coll_id_fk')
                ->references('id')
                ->on('verse_collections')
                ->cascadeOnDelete();
            $table->string('locale', 10)->index();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('slug')->nullable();
            $table->timestamps();

            $table->unique(['verse_collection_id', 'locale'], 'verse_coll_trans_coll_locale_uq');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('verse_collection_translations');
    }
};
