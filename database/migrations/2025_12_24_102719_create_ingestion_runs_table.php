<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ingestion_runs', function (Blueprint $table) {
            $table->id();
            $table->string('job_name', 100);
            $table->enum('status', ['running', 'success', 'fail', 'partial'])->default('running');
            $table->dateTime('started_at');
            $table->dateTime('finished_at')->nullable();
            $table->json('stats')->nullable();
            $table->json('checkpoint')->nullable();
            $table->longText('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ingestion_runs');
    }
};
