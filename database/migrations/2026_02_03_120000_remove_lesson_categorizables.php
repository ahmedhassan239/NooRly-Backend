<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Remove lesson–category pivot rows; lessons no longer use categories.
     * The categorizables table is kept for Duas and Daily Tasks.
     */
    public function up(): void
    {
        $lessonType = 'App\Domain\Lessons\Lesson';
        DB::table('categorizables')
            ->where('categorizable_type', $lessonType)
            ->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No-op: we do not restore deleted pivot rows
    }
};
