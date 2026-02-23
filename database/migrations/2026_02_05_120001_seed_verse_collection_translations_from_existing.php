<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Seed default translations from existing verse_collections.title (locale 'en').
     */
    public function up(): void
    {
        if (!Schema::hasTable('verse_collection_translations')) {
            return;
        }

        $collections = DB::table('verse_collections')->get();

        foreach ($collections as $row) {
            $exists = DB::table('verse_collection_translations')
                ->where('verse_collection_id', $row->id)
                ->where('locale', 'en')
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('verse_collection_translations')->insert([
                'verse_collection_id' => $row->id,
                'locale' => 'en',
                'title' => $row->title ?? 'Collection',
                'description' => null,
                'slug' => $row->slug ?? Str::slug($row->title ?? 'collection'),
                'created_at' => $row->created_at ?? now(),
                'updated_at' => $row->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('verse_collection_translations')) {
            return;
        }

        DB::table('verse_collection_translations')
            ->where('locale', 'en')
            ->delete();
    }
};
