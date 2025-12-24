<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_providers', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('app_user_id')->constrained('app_users')->onDelete('cascade');
            $blueprint->enum('provider', ['guest', 'email', 'google', 'facebook', 'apple']);
            $blueprint->string('provider_user_id')->nullable();
            $blueprint->string('email')->nullable();
            $blueprint->json('meta')->nullable();
            $blueprint->timestamps();
            
            $blueprint->index(['provider', 'provider_user_id']);
            $blueprint->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_providers');
    }
};
