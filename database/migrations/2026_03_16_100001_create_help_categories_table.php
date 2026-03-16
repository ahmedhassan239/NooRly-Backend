<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('help_categories', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('icon', 50)->default('heart');
            $table->boolean('is_active')->default(true)->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('description_en')->nullable();
            $table->string('description_ar')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('help_categories');
    }
};
