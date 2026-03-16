<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->constrained('help_categories')->cascadeOnDelete();
            $table->string('slug', 120);
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('subtitle_en')->nullable();
            $table->string('subtitle_ar')->nullable();
            $table->longText('content_en');
            $table->longText('content_ar');
            $table->timestamps();

            $table->unique(['category_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_items');
    }
};
