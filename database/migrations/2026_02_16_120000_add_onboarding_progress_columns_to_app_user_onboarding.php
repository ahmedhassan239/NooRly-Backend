<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds persistent onboarding progress: goals, summary_completed, current_step, completed_at.
     */
    public function up(): void
    {
        Schema::table('app_user_onboarding', function (Blueprint $table) {
            $table->json('goals')->nullable()->after('learning_goal');
            $table->boolean('summary_completed')->default(false)->after('goals');
            $table->string('current_step', 50)->nullable()->default('shahada_date')->after('summary_completed');
            $table->timestamp('completed_at')->nullable()->after('current_step');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_user_onboarding', function (Blueprint $table) {
            $table->dropColumn(['goals', 'summary_completed', 'current_step', 'completed_at']);
        });
    }
};
