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
        Schema::create('categorizables', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('category_id');
            $table->string('categorizable_type');
            $table->unsignedBigInteger('categorizable_id');
            $table->timestamps();

            $table->index('category_id');
            $table->index(['categorizable_type', 'categorizable_id']);
            $table->unique(['category_id', 'categorizable_type', 'categorizable_id'], 'categorizables_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('categorizables');
    }
};
