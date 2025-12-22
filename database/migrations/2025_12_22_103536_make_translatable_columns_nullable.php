<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Make old translatable columns nullable
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->json('content')->nullable()->change();
        });

        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
        });

        Schema::table('duas', function (Blueprint $table) {
            $table->string('title')->nullable()->change();
            $table->text('translation')->nullable()->change();
            $table->text('transliteration')->nullable()->change();
        });

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->string('name')->nullable()->change();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question')->nullable()->change();
            $table->text('answer')->nullable()->change();
        });
    }

    public function down(): void
    {
        // Reverse: make columns required again
        Schema::table('lessons', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->json('content')->nullable(false)->change();
        });

        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
        });

        Schema::table('duas', function (Blueprint $table) {
            $table->string('title')->nullable(false)->change();
            $table->text('translation')->nullable(false)->change();
        });

        Schema::table('faq_categories', function (Blueprint $table) {
            $table->string('name')->nullable(false)->change();
        });

        Schema::table('faqs', function (Blueprint $table) {
            $table->string('question')->nullable(false)->change();
            $table->text('answer')->nullable(false)->change();
        });
    }
};
