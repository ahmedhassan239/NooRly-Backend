<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_preferences', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->foreign('user_id')->references('id')->on('app_users')->onDelete('cascade');

            // --- Prayer ---
            $table->boolean('prayer_enabled')->default(true);
            $table->boolean('fajr_enabled')->default(true);
            $table->boolean('dhuhr_enabled')->default(true);
            $table->boolean('asr_enabled')->default(true);
            $table->boolean('maghrib_enabled')->default(true);
            $table->boolean('isha_enabled')->default(true);
            $table->enum('prayer_timing_mode', ['before', 'at', 'after'])->default('at');
            $table->smallInteger('prayer_offset_minutes')->default(0); // -15 to +30

            // --- Lesson ---
            $table->boolean('lesson_enabled')->default(true);
            $table->time('lesson_time')->nullable(); // null = 09:00 default
            $table->boolean('lesson_evening_reminder_enabled')->default(true);
            $table->boolean('streak_reminder_enabled')->default(true);

            // --- Adhkar ---
            $table->boolean('morning_adhkar_enabled')->default(true);
            $table->boolean('evening_adhkar_enabled')->default(true);
            $table->boolean('sleep_adhkar_enabled')->default(true);
            $table->time('sleep_adhkar_time')->nullable(); // null = 22:00 default
            $table->boolean('random_dhikr_enabled')->default(false);
            $table->unsignedTinyInteger('random_dhikr_frequency')->default(2); // per day

            // --- Milestone & Occasions ---
            $table->boolean('milestone_enabled')->default(true);
            $table->boolean('special_occasions_enabled')->default(true);
            $table->boolean('support_reminders_enabled')->default(true);

            // --- Quiet Hours ---
            $table->boolean('quiet_hours_enabled')->default(true);
            $table->time('quiet_hours_start')->nullable(); // null = 23:00 default
            $table->time('quiet_hours_end')->nullable();   // null = 05:00 default

            // --- Sound & Vibration ---
            $table->string('notification_sound')->nullable(); // null = system default
            $table->boolean('vibration_enabled')->default(true);

            // --- Language ---
            $table->enum('language_mode', ['app_locale', 'arabic', 'english', 'both'])->default('app_locale');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_preferences');
    }
};
