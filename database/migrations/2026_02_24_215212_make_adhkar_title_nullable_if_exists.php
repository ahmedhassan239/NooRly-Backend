<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Makes adhkar.title nullable so inserts without title succeed
     * (title was removed from the app; drop_title migration may not have run yet).
     */
    public function up(): void
    {
        if (!Schema::hasColumn('adhkar', 'title')) {
            return;
        }
        Schema::table('adhkar', function (Blueprint $table) {
            $table->json('title')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasColumn('adhkar', 'title')) {
            return;
        }
        Schema::table('adhkar', function (Blueprint $table) {
            $table->json('title')->nullable(false)->change();
        });
    }
};
