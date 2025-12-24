<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_user_profiles', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->foreignId('app_user_id')->constrained('app_users')->onDelete('cascade');
            $blueprint->string('name')->nullable();
            $blueprint->enum('gender', ['male', 'female', 'other', 'unknown'])->default('unknown');
            $blueprint->date('birth_date')->nullable();
            $blueprint->string('locale')->default('en');
            $blueprint->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_user_profiles');
    }
};
