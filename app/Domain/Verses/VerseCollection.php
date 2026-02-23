<?php

namespace App\Domain\Verses;

use App\Domain\Categories\Models\Category;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

class VerseCollection extends Model
{
    protected $table = 'verse_collections';

    protected $fillable = [
        'title',
        'slug',
        'icon',
        'color',
        'display_order',
    ];

    protected $casts = [
        'display_order' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Translations for this collection (title, description, slug per locale).
     */
    public function translations(): HasMany
    {
        return $this->hasMany(VerseCollectionTranslation::class, 'verse_collection_id');
    }

    /**
     * Get title for locale with fallback: requested locale -> 'en' -> first translation -> main table title.
     */
    public function getTitle(?string $locale = null, string $fallback = 'en'): string
    {
        return (string) $this->getTranslatedAttribute('title', $locale, $fallback);
    }

    /**
     * Get description for locale with fallback.
     */
    public function getDescription(?string $locale = null, string $fallback = 'en'): ?string
    {
        return $this->getTranslatedAttribute('description', $locale, $fallback);
    }

    /**
     * Get slug for locale with fallback.
     */
    public function getSlug(?string $locale = null, string $fallback = 'en'): ?string
    {
        return $this->getTranslatedAttribute('slug', $locale, $fallback);
    }

    /**
     * Get a translated attribute with fallback.
     */
    protected function getTranslatedAttribute(string $attribute, ?string $locale = null, string $fallback = 'en'): mixed
    {
        $locale = $locale ?? app()->getLocale();
        $translations = $this->translations;

        $byLocale = $translations->firstWhere('locale', $locale);
        if ($byLocale && ($attribute === 'title' ? true : filled($byLocale->{$attribute}))) {
            $value = $byLocale->{$attribute};
            if ($attribute === 'title' && $value === '') {
                // fall through
            } else {
                return $value;
            }
        }

        if ($locale !== $fallback) {
            $byFallback = $translations->firstWhere('locale', $fallback);
            if ($byFallback && ($attribute === 'title' ? true : filled($byFallback->{$attribute}))) {
                return $byFallback->{$attribute};
            }
        }

        $first = $translations->first();
        if ($first && ($attribute === 'title' ? true : filled($first->{$attribute}))) {
            return $first->{$attribute};
        }

        if ($attribute === 'title') {
            return $this->getRawOriginal('title') ?? $this->title ?? '';
        }
        if ($attribute === 'slug') {
            return $this->getRawOriginal('slug') ?? $this->slug;
        }
        return null;
    }

    /**
     * Categories this collection is attached to.
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_verse_coll')
            ->withTimestamps();
    }

    /**
     * Quran ayah IDs in this collection (external DB).
     */
    public function getQuranAyahIds(): array
    {
        return DB::table('verse_collection_ayah')
            ->where('verse_collection_id', $this->id)
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('quran_ayah_id')
            ->toArray();
    }

    /**
     * Sync Quran ayah IDs and display order.
     */
    public function syncQuranAyahs(array $items): void
    {
        DB::table('verse_collection_ayah')
            ->where('verse_collection_id', $this->id)
            ->delete();

        foreach ($items as $order => $item) {
            $ayahId = is_array($item) ? ($item['quran_ayah_id'] ?? $item['id'] ?? null) : $item;
            if ($ayahId === null) {
                continue;
            }
            DB::table('verse_collection_ayah')->insert([
                'verse_collection_id' => $this->id,
                'quran_ayah_id' => $ayahId,
                'display_order' => is_array($item) ? ($item['display_order'] ?? $order) : $order,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
