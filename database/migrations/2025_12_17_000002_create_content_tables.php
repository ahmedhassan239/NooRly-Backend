<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->integer('day_number')->index(); // 1 to 90
            $table->string('title');
            $table->json('content'); // structured content
            $table->string('type')->default('text'); // text, video
            $table->string('video_url')->nullable();
            $table->integer('duration_minutes')->default(5);
            $table->timestamps();
        });

        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->id();
            $table->integer('day_number')->index();
            $table->string('title');
            $table->string('type'); // prayer, action, read, sunnah
            $table->integer('points')->default(10);
            $table->timestamps();
        });

        Schema::create('duas', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('arabic');
            $table->text('translation');
            $table->text('transliteration')->nullable();
            $table->string('category')->nullable();
            $table->timestamps();
        });

        Schema::create('faq_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->timestamps();
        });

        Schema::create('faqs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('faq_category_id')->constrained()->cascadeOnDelete();
            $table->string('question');
            $table->text('answer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('faqs');
        Schema::dropIfExists('faq_categories');
        Schema::dropIfExists('duas');
        Schema::dropIfExists('daily_tasks');
        Schema::dropIfExists('lessons');
    }
};
