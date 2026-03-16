<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ramadan_guide_items', function (Blueprint $table) {
            $table->id();
            $table->string('slug', 120)->unique();
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->string('icon', 50)->default('moon');
            $table->boolean('is_active')->default(true)->index();
            $table->string('title_en');
            $table->string('title_ar');
            $table->string('description_en');
            $table->string('description_ar');
            $table->longText('content_en');
            $table->longText('content_ar');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ramadan_guide_items');
    }
};
