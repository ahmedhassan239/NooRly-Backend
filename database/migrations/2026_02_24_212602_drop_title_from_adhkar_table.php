<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove title field; UI uses main text as display content.
     */
    public function up(): void
    {
        Schema::table('adhkar', function (Blueprint $table) {
            $table->dropColumn('title');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('adhkar', function (Blueprint $table) {
            $table->json('title')->nullable()->after('category_id');
        });
    }
};
