<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notification_campaigns', function (Blueprint $table) {
            $table->id();
            $table->string('type', 64)->index();
            $table->string('audience_type', 64)->index();
            $table->json('audience_filters')->nullable();

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();

            $table->string('route')->nullable();
            $table->string('image_url')->nullable();
            $table->string('priority', 32)->nullable();

            $table->string('send_mode', 16)->default('now'); // now, scheduled
            $table->dateTime('scheduled_for')->nullable()->index();

            $table->string('status', 32)->default('draft')->index();
            // draft, scheduled, processing, sent, partial, failed, cancelled

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

            $table->unsignedInteger('sent_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->unsignedInteger('skipped_count')->default(0);
            $table->dateTime('processed_at')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_campaigns');
    }
};
