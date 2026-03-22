<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Longer icon keys (slug from filename, e.g. ramadhan-night-icon).
 * Uses raw SQL for MySQL/MariaDB to avoid requiring doctrine/dbal for ->change().
 */
return new class extends Migration
{
    public function up(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE ramadan_guide_items MODIFY icon VARCHAR(120) NOT NULL DEFAULT \'moon\'');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ramadan_guide_items ALTER COLUMN icon TYPE VARCHAR(120)');
        } else {
            Schema::table('ramadan_guide_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('icon', 120)->default('moon')->change();
            });
        }
    }

    public function down(): void
    {
        $driver = Schema::getConnection()->getDriverName();
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            DB::statement('ALTER TABLE ramadan_guide_items MODIFY icon VARCHAR(50) NOT NULL DEFAULT \'moon\'');
        } elseif ($driver === 'pgsql') {
            DB::statement('ALTER TABLE ramadan_guide_items ALTER COLUMN icon TYPE VARCHAR(50)');
        } else {
            Schema::table('ramadan_guide_items', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->string('icon', 50)->default('moon')->change();
            });
        }
    }
};
