<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_templates', function (Blueprint $table) {
            $table->id();
            $table->string('key')->index();
            $table->string('category'); // prayer, lesson, dhikr, milestone, occasion, support
            $table->string('sub_type'); // fajr, lesson_morning, milestone_day_complete, etc.
            $table->string('locale', 5)->default('en'); // en, ar
            $table->string('title');
            $table->text('body');
            $table->string('cta')->nullable();
            $table->json('variables')->nullable(); // list of placeholder keys used
            $table->enum('priority', ['high', 'medium', 'low'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->string('variation_group')->nullable(); // group key for rotating variants
            $table->unsignedTinyInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['key', 'locale']);
            $table->index(['category', 'sub_type', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_templates');
    }
};
