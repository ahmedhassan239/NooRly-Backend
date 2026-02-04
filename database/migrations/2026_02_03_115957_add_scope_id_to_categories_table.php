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
            }
        });

        // Add index if it doesn't exist
        $connection = Schema::getConnection();
        $indexes = $connection->select("SHOW INDEXES FROM categories WHERE Key_name = 'categories_scope_id_index'");
        if (empty($indexes)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->index('scope_id');
            });
        }

        // Add foreign key if it doesn't exist
        $foreignKeys = $connection->select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'categories' 
            AND CONSTRAINT_NAME = 'categories_scope_id_foreign'
        ");
        if (empty($foreignKeys)) {
            Schema::table('categories', function (Blueprint $table) {
                $table->foreign('scope_id')
                    ->references('id')
                    ->on('content_scopes')
                    ->onDelete('restrict');
            });
        }
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
