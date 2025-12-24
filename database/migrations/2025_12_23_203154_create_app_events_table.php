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
        Schema::create('app_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_user_id')->nullable()->constrained('app_users')->nullOnDelete();
            $table->string('event_type'); // share, open, complete, etc.
            $table->string('entity_type'); // dua, hadith, lesson, task, step, etc.
            $table->string('entity_id');
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['event_type', 'entity_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('app_events');
    }
};
