<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('scheduled_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->foreign('user_id')->references('id')->on('app_users')->onDelete('cascade');

            $table->string('category');   // milestone, occasion, support
            $table->string('sub_type');   // day_complete, friday, inactive_3_days, etc.

            $table->string('title_ar')->nullable();
            $table->string('title_en')->nullable();
            $table->text('body_ar')->nullable();
            $table->text('body_en')->nullable();

            $table->dateTime('scheduled_for')->index();
            $table->enum('status', ['pending', 'processed', 'cancelled', 'failed'])->default('pending')->index();
            $table->json('payload')->nullable(); // extra context (lesson_id, day_number, etc.)

            $table->timestamps();

            $table->index(['user_id', 'status', 'scheduled_for']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('scheduled_notifications');
    }
};
