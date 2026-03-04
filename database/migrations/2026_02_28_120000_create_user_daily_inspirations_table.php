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
        Schema::create('user_daily_inspirations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_user_id')->index();
            $table->string('type', 32); // hadith, verse, dua, adhkar
            $table->unsignedBigInteger('item_id');
            $table->timestamp('selected_at');
            $table->timestamp('expires_at');
            $table->unsignedBigInteger('previous_item_id')->nullable();
            $table->timestamps();

            $table->unique('app_user_id');
            $table->foreign('app_user_id')->references('id')->on('app_users')->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_daily_inspirations');
    }
};
