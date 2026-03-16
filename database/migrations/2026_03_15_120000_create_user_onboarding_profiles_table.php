<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * One-to-one onboarding profile per app user (display name, journey, goals, preferences).
     */
    public function up(): void
    {
        Schema::create('user_onboarding_profiles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('display_name', 255)->nullable();
            $table->string('embrace_islam_range', 50)->nullable();
            $table->string('arabic_level', 50)->nullable();
            $table->string('prayer_level', 50)->nullable();
            $table->string('quran_reading_level', 50)->nullable();
            $table->json('goals')->nullable();
            $table->json('challenges')->nullable();
            $table->string('daily_time', 50)->nullable();
            $table->string('preferred_learning_time', 50)->nullable();
            $table->string('learning_style', 50)->nullable();
            $table->string('reminder_preference', 50)->nullable();
            $table->date('islam_date')->nullable();
            $table->timestamp('onboarding_completed_at')->nullable();
            $table->timestamps();

            $table->unique('app_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_onboarding_profiles');
    }
};
