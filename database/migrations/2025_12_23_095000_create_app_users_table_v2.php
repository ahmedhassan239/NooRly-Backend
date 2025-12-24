<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_users', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->uuid('uuid')->unique();
            $blueprint->enum('status', ['active', 'disabled', 'banned'])->default('active');
            $blueprint->timestamp('last_active_at')->nullable();
            $blueprint->timestamps();
            $blueprint->softDeletes();
            
            $blueprint->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_users');
    }
};
