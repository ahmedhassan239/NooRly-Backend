<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $blueprint) {
            $blueprint->id();
            $blueprint->string('provider');
            $blueprint->string('endpoint');
            $blueprint->enum('status', ['success', 'fail']);
            $blueprint->integer('http_code');
            $blueprint->integer('duration_ms');
            $blueprint->text('message')->nullable();
            $blueprint->string('payload_hash')->nullable();
            $blueprint->timestamp('created_at')->useCurrent();
            
            $blueprint->index('provider');
            $blueprint->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
