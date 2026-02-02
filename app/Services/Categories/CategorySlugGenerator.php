<?php

namespace App\Services\Categories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Service for generating unique category slugs per language.
 */
class CategorySlugGenerator
{
    /**
     * Generate a unique slug for a category in a specific language.
     *
     * @param string $name The category name to slugify
     * @param string $languageCode The language code
     * @param int|null $excludeCategoryId Category ID to exclude (for updates)
     * @return string The unique slug
     */
    public function generate(string $name, string $languageCode, ?int $excludeCategoryId = null): string
    {
        $baseSlug = $this->slugify($name, $languageCode);
        
        return $this->makeUnique($baseSlug, $languageCode, $excludeCategoryId);
    }

    /**
     * Generate a slug from a name, handling RTL languages properly.
     *
     * @param string $name The name to slugify
     * @param string $languageCode The language code
     * @return string The slug
     */
    protected function slugify(string $name, string $languageCode): string
    {
        // For Arabic and other RTL languages, use the name as-is but make it URL-safe
        if (in_array($languageCode, ['ar', 'fa', 'ur', 'he'])) {
            return $this->slugifyArabic($name);
        }

        return Str::slug($name);
    }

    /**
     * Create a URL-safe slug for Arabic text.
     * Preserves Arabic characters while making it URL-safe.
     *
     * @param string $text The Arabic text
     * @return string The slug
     */
    protected function slugifyArabic(string $text): string
    {
        // Remove extra whitespace
        $text = preg_replace('/\s+/', ' ', trim($text));
        
        // Replace spaces with hyphens
        $text = str_replace(' ', '-', $text);
        
        // Remove characters that aren't Arabic, alphanumeric, or hyphens
        $text = preg_replace('/[^\p{Arabic}a-zA-Z0-9\-]/u', '', $text);
        
        // Remove multiple consecutive hyphens
        $text = preg_replace('/-+/', '-', $text);
        
        // Trim hyphens from start and end
        return trim($text, '-');
    }

    /**
     * Make a slug unique by appending a number if necessary.
     *
     * @param string $slug The base slug
     * @param string $languageCode The language code
     * @param int|null $excludeCategoryId Category ID to exclude
     * @return string The unique slug
     */
    protected function makeUnique(string $slug, string $languageCode, ?int $excludeCategoryId = null): string
    {
        $originalSlug = $slug;
        $counter = 1;

        while ($this->slugExists($slug, $languageCode, $excludeCategoryId)) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;

            // Safety limit to prevent infinite loops
            if ($counter > 100) {
                $slug = "{$originalSlug}-" . uniqid();
                break;
            }
        }

        return $slug;
    }

    /**
     * Check if a slug already exists in the database.
     *
     * @param string $slug The slug to check
     * @param string $languageCode The language code
     * @param int|null $excludeCategoryId Category ID to exclude
     * @return bool
     */
    protected function slugExists(string $slug, string $languageCode, ?int $excludeCategoryId = null): bool
    {
        $query = DB::table('category_translations')
            ->where('language_code', $languageCode)
            ->where('slug', $slug);

        if ($excludeCategoryId !== null) {
            $query->where('category_id', '!=', $excludeCategoryId);
        }

        return $query->exists();
    }

    /**
     * Validate that a slug is unique.
     *
     * @param string $slug The slug to validate
     * @param string $languageCode The language code
     * @param int|null $excludeCategoryId Category ID to exclude
     * @return bool True if unique, false if exists
     */
    public function isUnique(string $slug, string $languageCode, ?int $excludeCategoryId = null): bool
    {
        return !$this->slugExists($slug, $languageCode, $excludeCategoryId);
    }
}
