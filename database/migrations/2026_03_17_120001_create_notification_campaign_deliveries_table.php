<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaign_deliveries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('notification_campaigns')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('app_users')->cascadeOnDelete();

            $table->string('platform', 16)->nullable();
            $table->string('provider', 32)->nullable();
            $table->string('provider_message_id')->nullable();

            $table->string('delivery_status', 32)->default('pending')->index();
            // pending, sent, failed, skipped, provider_unavailable

            $table->text('failure_reason')->nullable();
            $table->dateTime('sent_at')->nullable();
            $table->dateTime('opened_at')->nullable();

            $table->timestamps();

            $table->unique(['campaign_id', 'user_id']);
            $table->index(['user_id', 'delivery_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaign_deliveries');
    }
};
