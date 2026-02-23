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
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->string('icon_key', 64)->nullable()->after('label');
            $table->string('icon_color', 32)->nullable()->after('icon_key');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->dropColumn(['icon_key', 'icon_color']);
        });
    }
};
