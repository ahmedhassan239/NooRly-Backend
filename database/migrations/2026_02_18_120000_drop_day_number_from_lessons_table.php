<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropIndex(['day_number']);
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->dropColumn('day_number');
        });
    }

    public function down(): void
    {
        Schema::table('lessons', function (Blueprint $table) {
            $table->integer('day_number')->nullable()->after('id');
        });
        Schema::table('lessons', function (Blueprint $table) {
            $table->index('day_number');
        });
    }
};
