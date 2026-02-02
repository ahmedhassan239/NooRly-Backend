<?php

use App\Domain\Categories\Models\Category;
use App\Domain\Categories\Models\CategoryTranslation;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * This migration:
     * 1. Copies existing category data to translations table (as English)
     * 2. Keeps the original columns for backward compatibility (can be removed later)
     */
    public function up(): void
    {
        // Check if there are existing categories with data in name/slug columns
        if (!Schema::hasColumn('categories', 'name')) {
            // No legacy columns, nothing to migrate
            return;
        }

        // Get all existing categories
        $categories = DB::table('categories')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get();

        foreach ($categories as $category) {
            // Check if translation already exists
            $existingTranslation = DB::table('category_translations')
                ->where('category_id', $category->id)
                ->where('language_code', 'en')
                ->exists();

            if ($existingTranslation) {
                continue;
            }

            // Create English translation from existing data
            DB::table('category_translations')->insert([
                'category_id' => $category->id,
                'language_code' => 'en',
                'name' => $category->name,
                'slug' => $category->slug ?? Str::slug($category->name),
                'description' => $category->description,
                'created_at' => $category->created_at ?? now(),
                'updated_at' => $category->updated_at ?? now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Delete migrated translations (only those that match old data exactly)
        // This is a best-effort rollback
        $categories = DB::table('categories')
            ->whereNotNull('name')
            ->where('name', '!=', '')
            ->get();

        foreach ($categories as $category) {
            DB::table('category_translations')
                ->where('category_id', $category->id)
                ->where('language_code', 'en')
                ->where('name', $category->name)
                ->delete();
        }
    }
};
