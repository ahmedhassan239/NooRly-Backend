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
        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'scope_id')) {
                $table->unsignedBigInteger('scope_id')->nullable()->after('id');
                $table->index('scope_id');
                $table->foreign('scope_id')
                    ->references('id')
                    ->on('content_scopes')
                    ->onDelete('restrict');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            $table->dropForeign(['scope_id']);
            $table->dropIndex(['scope_id']);
            $table->dropColumn('scope_id');
        });
    }
};
