<?php

namespace App\Domain\Categories\Models;

use App\Domain\Hadith\Models\HadithItem;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\Traits\HasTranslations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Category Model
 * 
 * Represents a category that can group Quran verses (Ayat) and Hadith items.
 * Supports multi-language translations for name, slug, and description.
 * 
 * @property int $id
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \Illuminate\Database\Eloquent\Collection|CategoryTranslation[] $translations
 * @property-read \Illuminate\Database\Eloquent\Collection|QuranVerse[] $verses
 * @property-read \Illuminate\Database\Eloquent\Collection|HadithItem[] $hadiths
 */
class Category extends Model
{
    use HasTranslations;

    protected $table = 'categories';

    protected $fillable = [
        // Base fields only (translations are in separate table)
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // =========================================================================
    // HasTranslations Implementation
    // =========================================================================

    /**
     * Get the translation table name.
     */
    protected function getTranslationTable(): string
    {
        return 'category_translations';
    }

    /**
     * Get the foreign key name for translations.
     */
    protected function getTranslationForeignKey(): string
    {
        return 'category_id';
    }

    /**
     * Get translatable field names.
     */
    protected function getTranslatableFields(): array
    {
        return ['name', 'slug', 'description'];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    /**
     * Get all translations for this category.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(CategoryTranslation::class);
    }

    /**
     * Get the verse IDs associated with this category.
     * 
     * Note: This is a cross-database relationship. The verses are stored
     * in the quran_all_lang database, while the pivot table is in the main DB.
     * We must query the pivot table directly to avoid cross-database JOIN issues.
     */
    public function getVerseIds(): array
    {
        return DB::table('category_verse')
            ->where('category_id', $this->id)
            ->pluck('verse_id')
            ->toArray();
    }

    /**
     * Get the Quran verses associated with this category.
     * 
     * Note: This fetches verses by their IDs from the separate database.
     * Does NOT use Eloquent relationship due to cross-database limitations.
     */
    public function getVerses()
    {
        $verseIds = $this->getVerseIds();
        
        if (empty($verseIds)) {
            return collect();
        }
        
        return QuranVerse::whereIn('id', $verseIds)->get();
    }

    /**
     * Sync verse IDs to the pivot table.
     */
    public function syncVerses(array $verseIds): void
    {
        DB::table('category_verse')
            ->where('category_id', $this->id)
            ->delete();

        if (!empty($verseIds)) {
            $inserts = array_map(fn ($id) => [
                'category_id' => $this->id,
                'verse_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $verseIds);

            DB::table('category_verse')->insert($inserts);
        }
    }

    /**
     * Get the hadith IDs associated with this category.
     * 
     * Note: This is a cross-database relationship. The hadiths are stored
     * in the hadith database, while the pivot table is in the main DB.
     */
    public function getHadithIds(): array
    {
        return DB::table('category_hadith')
            ->where('category_id', $this->id)
            ->pluck('hadith_id')
            ->toArray();
    }

    /**
     * Sync hadith IDs to the pivot table.
     */
    public function syncHadiths(array $hadithIds): void
    {
        DB::table('category_hadith')
            ->where('category_id', $this->id)
            ->delete();

        if (!empty($hadithIds)) {
            $inserts = array_map(fn ($id) => [
                'category_id' => $this->id,
                'hadith_id' => $id,
                'created_at' => now(),
                'updated_at' => now(),
            ], $hadithIds);

            DB::table('category_hadith')->insert($inserts);
        }
    }

    // =========================================================================
    // Translation Helpers
    // =========================================================================

    /**
     * Get the name for a specific locale.
     * 
     * @param string|null $locale Language code (defaults to app locale)
     * @param string $fallback Fallback language code
     * @return string|null
     */
    public function getName(?string $locale = null, string $fallback = 'en'): ?string
    {
        return $this->getTranslatedAttribute('name', $locale, $fallback);
    }

    /**
     * Get the slug for a specific locale.
     * 
     * @param string|null $locale Language code (defaults to app locale)
     * @param string $fallback Fallback language code
     * @return string|null
     */
    public function getSlug(?string $locale = null, string $fallback = 'en'): ?string
    {
        return $this->getTranslatedAttribute('slug', $locale, $fallback);
    }

    /**
     * Get the description for a specific locale.
     * 
     * @param string|null $locale Language code (defaults to app locale)
     * @param string $fallback Fallback language code
     * @return string|null
     */
    public function getDescription(?string $locale = null, string $fallback = 'en'): ?string
    {
        return $this->getTranslatedAttribute('description', $locale, $fallback);
    }

    /**
     * Get a translated attribute value with fallback.
     * 
     * @param string $attribute Attribute name
     * @param string|null $locale Language code
     * @param string $fallback Fallback language code
     * @return string|null
     */
    protected function getTranslatedAttribute(string $attribute, ?string $locale = null, string $fallback = 'en'): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Try requested locale
        $translation = $this->translations->firstWhere('language_code', $locale);
        if ($translation && !empty($translation->{$attribute})) {
            return $translation->{$attribute};
        }
        
        // Try fallback locale
        if ($locale !== $fallback) {
            $translation = $this->translations->firstWhere('language_code', $fallback);
            if ($translation && !empty($translation->{$attribute})) {
                return $translation->{$attribute};
            }
        }
        
        // Return first available
        $first = $this->translations->first();
        return $first?->{$attribute};
    }

    /**
     * Get a translation for a specific language code.
     * 
     * @param string $languageCode
     * @return CategoryTranslation|null
     */
    public function getTranslation(string $languageCode): ?CategoryTranslation
    {
        return $this->translations->firstWhere('language_code', $languageCode);
    }

    /**
     * Check if a translation exists for a language.
     * 
     * @param string $languageCode
     * @return bool
     */
    public function hasTranslation(string $languageCode): bool
    {
        return $this->translations->contains('language_code', $languageCode);
    }

    /**
     * Get the count of translations.
     */
    public function getTranslationsCountAttribute(): int
    {
        return $this->translations()->count();
    }

    // =========================================================================
    // Attribute Accessors
    // =========================================================================

    /**
     * Get the count of associated verses.
     */
    public function getVersesCountAttribute(): int
    {
        return DB::table('category_verse')
            ->where('category_id', $this->id)
            ->count();
    }

    /**
     * Get the count of associated hadiths.
     */
    public function getHadithsCountAttribute(): int
    {
        return DB::table('category_hadith')
            ->where('category_id', $this->id)
            ->count();
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    /**
     * Scope to search by translated name.
     */
    public function scopeSearchByName(Builder $query, string $term, ?string $locale = null): Builder
    {
        return $query->whereHas('translations', function (Builder $q) use ($term, $locale) {
            $q->where('name', 'like', "%{$term}%");
            if ($locale) {
                $q->where('language_code', $locale);
            }
        });
    }

    /**
     * Scope to find by translated slug.
     */
    public function scopeBySlug(Builder $query, string $slug, ?string $locale = null): Builder
    {
        return $query->whereHas('translations', function (Builder $q) use ($slug, $locale) {
            $q->where('slug', $slug);
            if ($locale) {
                $q->where('language_code', $locale);
            }
        });
    }

    /**
     * Scope to filter by language.
     */
    public function scopeWithLanguage(Builder $query, string $locale): Builder
    {
        return $query->whereHas('translations', function (Builder $q) use ($locale) {
            $q->where('language_code', $locale);
        });
    }
}
