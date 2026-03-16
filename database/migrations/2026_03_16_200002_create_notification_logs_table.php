<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('app_users')->onDelete('set null');

            $table->string('category');      // prayer, lesson, dhikr, milestone, occasion, support
            $table->string('sub_type');      // fajr, lesson_morning, etc.
            $table->string('channel');       // local, fcm, apns, onesignal
            $table->enum('delivery_status', [
                'scheduled',
                'shown',
                'opened',
                'dismissed',
                'failed',
                'suppressed',
            ])->default('scheduled');

            $table->string('title');
            $table->text('body');
            $table->string('locale', 5)->default('en');
            $table->json('payload')->nullable();

            $table->dateTime('scheduled_for')->nullable()->index();
            $table->dateTime('delivered_at')->nullable();
            $table->dateTime('opened_at')->nullable();
            $table->string('suppression_reason')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'category', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_logs');
    }
};
