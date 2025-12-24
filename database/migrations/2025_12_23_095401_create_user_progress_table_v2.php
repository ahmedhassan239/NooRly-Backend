<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_progress', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('app_user_id')->constrained('app_users')->onDelete('cascade');
            $blueprint->date('date');
            $blueprint->json('completed_task_ids')->nullable();
            $blueprint->json('salah_completed_step_ids')->nullable();
            $blueprint->json('wudu_completed_step_ids')->nullable();
            $blueprint->integer('streak_count')->default(0);
            $blueprint->timestamps();
            
            $blueprint->index(['app_user_id', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_progress');
    }
};
