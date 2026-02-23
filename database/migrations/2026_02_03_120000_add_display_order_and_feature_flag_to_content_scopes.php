<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Adds display_order (tab order in app) and feature_flag (optional feature gating).
     */
    public function up(): void
    {
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->unsignedInteger('display_order')->default(0)->after('is_active');
            $table->string('feature_flag', 64)->nullable()->after('display_order');
            $table->index('display_order');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->dropColumn(['display_order', 'feature_flag']);
        });
    }
};
