<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->date('shahada_date')->nullable();
            $table->string('goal')->nullable();
            $table->string('timezone')->default('UTC');
            $table->integer('current_day')->default(1);
            $table->boolean('is_onboarded')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['shahada_date', 'goal', 'timezone', 'current_day', 'is_onboarded']);
        });
    }
};
