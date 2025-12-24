<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dataset_versions', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('dataset_type'); // duas/hadith/salah_steps/wudu_steps/faq/daily_tasks
            $blueprint->string('locale');
            $blueprint->string('version');
            $blueprint->string('file_path');
            $blueprint->string('checksum');
            $blueprint->boolean('is_published')->default(false);
            $blueprint->timestamp('published_at')->nullable();
            $blueprint->timestamps();
            
            $blueprint->index(['dataset_type', 'locale', 'is_published']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dataset_versions');
    }
};
