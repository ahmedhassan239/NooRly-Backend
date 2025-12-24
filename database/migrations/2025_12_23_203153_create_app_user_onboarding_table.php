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
        Schema::create('app_user_onboarding', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->date('start_date');
            $table->date('shahada_date')->nullable();
            $table->string('learning_goal')->nullable();
            $table->string('timezone')->nullable()->default('Africa/Cairo');
            $table->timestamps();

            $table->unique('app_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_user_onboarding');
    }
};
