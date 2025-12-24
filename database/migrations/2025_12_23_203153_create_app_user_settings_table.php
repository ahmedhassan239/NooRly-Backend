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
        Schema::create('app_user_settings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->constrained('app_users')->cascadeOnDelete();
            $table->string('language')->default('en');
            $table->boolean('dark_mode')->default(false);
            $table->boolean('notifications_enabled')->default(true);
            $table->enum('time_format', ['12', '24'])->default('12');
            $table->enum('location_mode', ['gps', 'manual'])->default('gps');
            $table->string('manual_city')->nullable();
            $table->string('manual_country')->nullable();
            $table->integer('prayer_calc_method')->nullable();
            $table->integer('prayer_madhab')->nullable();
            $table->json('prayer_adjustments')->nullable();
            $table->timestamps();

            $table->unique('app_user_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_user_settings');
    }
};
