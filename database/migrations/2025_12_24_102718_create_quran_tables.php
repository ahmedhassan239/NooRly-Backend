<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quran_surahs', function (Blueprint $table) {
            $table->unsignedInteger('surah_number')->primary();
            $table->string('name_ar')->nullable();
            $table->string('name_en')->nullable();
            $table->string('revelation_type', 50)->nullable();
            $table->unsignedInteger('ayahs_count')->default(0);
            $table->timestamps();
        });

        Schema::create('quran_ayahs', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('surah_number')->index();
            $table->unsignedInteger('ayah_number')->index();
            $table->unsignedInteger('global_ayah_number')->unique();
            $table->longText('text_ar');
            $table->timestamps();

            $table->unique(['surah_number', 'ayah_number'], 'uq_surah_ayah');
            $table->foreign('surah_number')->references('surah_number')->on('quran_surahs')->onDelete('cascade');
        });

        Schema::create('quran_editions', function (Blueprint $table) {
            $table->string('identifier', 64)->primary();
            $table->string('locale', 10)->index();
            $table->string('type', 30);
            $table->string('format', 30);
            $table->string('name')->nullable();
            $table->string('english_name')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });

        Schema::create('quran_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('global_ayah_number')->index();
            $table->string('locale', 10)->index();
            $table->string('edition_identifier', 64)->index();
            $table->string('translator_name')->nullable();
            $table->longText('text');
            $table->timestamps();

            $table->unique(['global_ayah_number', 'locale'], 'uq_translation');
            $table->foreign('edition_identifier')->references('identifier')->on('quran_editions')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quran_translations');
        Schema::dropIfExists('quran_editions');
        Schema::dropIfExists('quran_ayahs');
        Schema::dropIfExists('quran_surahs');
    }
};
