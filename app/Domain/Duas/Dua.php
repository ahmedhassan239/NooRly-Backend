<?php

namespace App\Domain\Duas;

use App\Domain\Categories\Models\Category;
use App\Domain\Hadith\Models\HadithItem;
use App\Domain\QuranAllLang\Models\QuranVerse;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Dua extends Model
{
    use \Illuminate\Database\Eloquent\Factories\HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\DuaFactory::new();
    }

    protected $fillable = [
        'dua_key',
        'category_key',
        'source',
        'text_ar',
        'transliteration',
        'text_en',
        'meta',
        'is_active',
        'is_featured',
        'position',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'position' => 'integer',
    ];

    /**
     * Get all translations for this dua.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(DuaTranslation::class);
    }

    /**
     * Get all categories for this dua.
     */
    public function categories(): MorphToMany
    {
        return $this->morphToMany(Category::class, 'categorizable', 'categorizables')
            ->withTimestamps();
    }

    /**
     * Get all Quran verses (Ayat) attached to this dua.
     */
    public function quranAyahs(): MorphToMany
    {
        return $this->morphToMany(
            QuranVerse::class,
            'ayahable',
            'quran_ayahables',
            'ayahable_id',
            'quran_ayah_id'
        )->withTimestamps();
    }

    /**
     * Get all Hadith items attached to this dua.
     */
    public function hadithItems(): MorphToMany
    {
        return $this->morphToMany(
            HadithItem::class,
            'hadithable',
            'hadith_itemables',
            'hadithable_id',
            'hadith_item_id'
        )->withTimestamps();
    }

    /**
     * Get a translated attribute value.
     * Falls back to direct column if no translation exists.
     * 
     * @param string $attribute Attribute name (name, text)
     * @param string|null $locale Language code
     * @return string|null
     */
    public function getTranslation(string $attribute, ?string $locale = null): ?string
    {
        $locale = $locale ?? app()->getLocale();
        
        // Try to get from translations table
        if ($this->relationLoaded('translations')) {
            $translation = $this->translations->firstWhere('language_code', $locale);
            if ($translation) {
                // Map attribute names
                $columnMap = [
                    'name' => 'title',
                    'text' => 'translation_text',
                ];
                $column = $columnMap[$attribute] ?? $attribute;
                if (!empty($translation->{$column})) {
                    return $translation->{$column};
                }
            }
        }
        
        // Fall back to direct columns
        if ($attribute === 'name') {
            return $this->dua_key; // Use key as name fallback
        }
        
        if ($attribute === 'text') {
            return $locale === 'ar' ? $this->text_ar : ($this->text_en ?? $this->text_ar);
        }
        
        return null;
    }
}
