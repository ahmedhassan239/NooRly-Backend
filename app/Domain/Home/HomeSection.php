<?php

namespace App\Domain\Home;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * HomeSection Model
 * 
 * Defines configurable sections for the app's home screen.
 * Each section can display different types of content (verses, hadith, duas, etc.)
 * with customizable ordering and visibility per locale.
 * 
 * @property int $id
 * @property string $key Unique section identifier
 * @property array $title Translatable title
 * @property array|null $subtitle Translatable subtitle
 * @property string $type Section type: featured, list, carousel, banner, single
 * @property string|null $source_type Content source: lessons, duas, hadith, verses, adhkar
 * @property array|null $query_config Query configuration (filters, limits, etc.)
 * @property string|null $icon Icon name
 * @property string|null $route Deep link route in app
 * @property int $position Display order
 * @property bool $is_active Whether section is visible
 * @property string|null $locale Specific locale or null for all
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class HomeSection extends Model
{
    protected $table = 'home_sections';

    protected $fillable = [
        'key',
        'title',
        'subtitle',
        'type',
        'source_type',
        'query_config',
        'icon',
        'route',
        'position',
        'is_active',
        'locale',
    ];

    protected $casts = [
        'title' => 'array',
        'subtitle' => 'array',
        'query_config' => 'array',
        'position' => 'integer',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected const CACHE_KEY = 'home_sections';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get active sections for a locale
     */
    public static function getForLocale(?string $locale = null): \Illuminate\Database\Eloquent\Collection
    {
        $cacheKey = self::CACHE_KEY . ':' . ($locale ?? 'all');

        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($locale) {
            return static::active()
                ->forLocale($locale)
                ->ordered()
                ->get();
        });
    }

    /**
     * Get title for a specific locale
     */
    public function getLocalizedTitle(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->title[$locale] ?? $this->title['en'] ?? '';
    }

    /**
     * Get subtitle for a specific locale
     */
    public function getLocalizedSubtitle(?string $locale = null): ?string
    {
        if (!$this->subtitle) {
            return null;
        }
        $locale = $locale ?? app()->getLocale();
        return $this->subtitle[$locale] ?? $this->subtitle['en'] ?? null;
    }

    /**
     * Scope to filter active sections
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter by locale (includes global sections)
     */
    public function scopeForLocale(Builder $query, ?string $locale = null): Builder
    {
        return $query->where(function ($q) use ($locale) {
            $q->whereNull('locale');
            if ($locale) {
                $q->orWhere('locale', $locale);
            }
        });
    }

    /**
     * Scope to order by position
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }

    /**
     * Scope to filter by source type
     */
    public function scopeBySourceType(Builder $query, string $sourceType): Builder
    {
        return $query->where('source_type', $sourceType);
    }

    /**
     * Clear cache
     */
    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY . ':all');
        Cache::forget(self::CACHE_KEY . ':en');
        Cache::forget(self::CACHE_KEY . ':ar');
    }

    /**
     * Boot method to clear cache on changes
     */
    protected static function booted(): void
    {
        static::saved(function () {
            static::clearCache();
        });

        static::deleted(function () {
            static::clearCache();
        });
    }

    /**
     * Convert to API format
     */
    public function toApiArray(?string $locale = null): array
    {
        return [
            'key' => $this->key,
            'title' => $this->getLocalizedTitle($locale),
            'subtitle' => $this->getLocalizedSubtitle($locale),
            'type' => $this->type,
            'source_type' => $this->source_type,
            'icon' => $this->icon,
            'route' => $this->route,
            'position' => $this->position,
        ];
    }
}
