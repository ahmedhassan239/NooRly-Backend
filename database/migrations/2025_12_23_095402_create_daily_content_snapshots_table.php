<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_content_snapshots', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->date('date');
            $blueprint->string('locale');
            $blueprint->unsignedBigInteger('dua_id')->nullable();
            $blueprint->unsignedBigInteger('hadith_id')->nullable();
            $blueprint->json('payload');
            $blueprint->timestamps();
            
            $blueprint->index(['date', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_content_snapshots');
    }
};
