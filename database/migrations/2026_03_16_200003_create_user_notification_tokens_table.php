<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_notification_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->foreign('user_id')->references('id')->on('app_users')->onDelete('cascade');

            $table->enum('platform', ['android', 'ios', 'web']);
            $table->text('token');
            // null until a push provider is integrated (fcm, apns, onesignal, etc.)
            $table->string('provider')->nullable();
            $table->boolean('is_active')->default(true);
            $table->dateTime('last_seen_at')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'platform', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_notification_tokens');
    }
};
