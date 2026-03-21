<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_inbox', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('app_users')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('notification_campaigns')->nullOnDelete();
            $table->foreignId('delivery_id')->nullable()->constrained('notification_campaign_deliveries')->nullOnDelete();

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();
            $table->string('route')->nullable();

            $table->boolean('is_read')->default(false)->index();
            $table->timestamps();

            $table->unique('delivery_id');
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_inbox');
    }
};
