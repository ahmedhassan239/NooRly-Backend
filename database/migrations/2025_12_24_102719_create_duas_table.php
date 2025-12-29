<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Handle legacy duas system to avoid conflicts
        if (Schema::hasTable('dua_translations')) {
            Schema::rename('dua_translations', 'legacy_dua_translations');
        }
        
        if (Schema::hasTable('duas')) {
            Schema::rename('duas', 'legacy_duas');
        }

        Schema::create('duas', function (Blueprint $table) {
            $table->id();
            $table->string('dua_key', 100)->unique();
            $table->string('category_key', 100)->index();
            $table->string('source')->nullable();
            $table->longText('text_ar');
            $table->longText('transliteration')->nullable();
            $table->longText('text_en')->nullable();
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duas');
        
        if (Schema::hasTable('legacy_duas')) {
            Schema::rename('legacy_duas', 'duas');
        }
        
        if (Schema::hasTable('legacy_dua_translations')) {
            Schema::rename('legacy_dua_translations', 'dua_translations');
        }
    }
};
