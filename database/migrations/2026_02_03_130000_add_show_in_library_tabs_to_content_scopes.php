<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * show_in_library_tabs: when true, scope appears in Library tabs (independent of active).
     */
    public function up(): void
    {
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->boolean('show_in_library_tabs')->default(true)->after('is_active');
        });
    }

    public function down(): void
    {
        Schema::table('content_scopes', function (Blueprint $table) {
            $table->dropColumn('show_in_library_tabs');
        });
    }
};
