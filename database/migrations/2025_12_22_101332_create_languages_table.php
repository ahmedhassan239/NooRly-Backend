<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('languages', function (Blueprint $table) {
            $table->id();
            $table->string('code', 10)->unique()->comment('ISO code: en, ar, fr, etc.');
            $table->string('name')->comment('English name');
            $table->string('native_name')->comment('Native name');
            $table->enum('direction', ['ltr', 'rtl'])->default('ltr');
            $table->boolean('is_active')->default(true);
            $table->boolean('is_default')->default(false);
            $table->timestamps();
            
            $table->index(['is_active', 'is_default']);
            $table->index('code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('languages');
    }
};
