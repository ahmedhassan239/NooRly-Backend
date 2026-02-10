<?php

namespace App\Domain\Adhkar;

use App\Domain\Categories\Models\Category;
use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Adhkar Model
 * 
 * Represents Islamic remembrances (adhkar) with translatable content.
 * Supports categories (morning, evening, sleep, etc.) and Arabic search.
 * 
 * @property int $id
 * @property int|null $category_id
 * @property array $title Translatable title
 * @property array $text Translatable dhikr text
 * @property string|null $text_ar_normalized Normalized Arabic text for search
 * @property int $count Number of times to repeat
 * @property array|null $reward Translatable reward/benefit
 * @property string|null $source Hadith source reference
 * @property string|null $audio_url URL to audio file
 * @property string|null $category_key Category key: morning, evening, sleep, etc.
 * @property int $position Display order
 * @property bool $is_active
 * @property bool $is_featured
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Category|null $category
 */
class Adhkar extends Model
{
    protected $table = 'adhkar';

    protected $fillable = [
        'category_id',
        'title',
        'text',
        'text_ar_normalized',
        'count',
        'reward',
        'source',
        'audio_url',
        'category_key',
        'position',
        'is_active',
        'is_featured',
    ];

    protected $casts = [
        'title' => 'array',
        'text' => 'array',
        'reward' => 'array',
        'count' => 'integer',
        'position' => 'integer',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Category relationship
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
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
     * Get text for a specific locale
     */
    public function getLocalizedText(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();
        return $this->text[$locale] ?? $this->text['ar'] ?? '';
    }

    /**
     * Get reward for a specific locale
     */
    public function getLocalizedReward(?string $locale = null): ?string
    {
        if (!$this->reward) {
            return null;
        }
        $locale = $locale ?? app()->getLocale();
        return $this->reward[$locale] ?? $this->reward['en'] ?? null;
    }

    /**
     * Scope to filter active adhkar
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter featured adhkar
     */
    public function scopeFeatured(Builder $query): Builder
    {
        return $query->where('is_featured', true);
    }

    /**
     * Scope to filter by category key
     */
    public function scopeByCategoryKey(Builder $query, string $key): Builder
    {
        return $query->where('category_key', $key);
    }

    /**
     * Scope to order by position
     */
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('position');
    }

    /**
     * Scope to search by normalized Arabic text
     */
    public function scopeSearchNormalized(Builder $query, string $term): Builder
    {
        $normalizedTerm = ArabicTextNormalizer::normalize($term);
        return $query->where('text_ar_normalized', 'like', "%{$normalizedTerm}%");
    }

    /**
     * Boot method to auto-normalize Arabic text
     */
    protected static function booted(): void
    {
        static::saving(function ($adhkar) {
            // Auto-normalize Arabic text for search
            if (isset($adhkar->text['ar'])) {
                $adhkar->text_ar_normalized = ArabicTextNormalizer::normalize($adhkar->text['ar']);
            }
        });
    }

    /**
     * Convert to API format
     */
    public function toApiArray(?string $locale = null): array
    {
        return [
            'id' => $this->id,
            'title' => $this->getLocalizedTitle($locale),
            'text' => $this->getLocalizedText($locale),
            'text_ar' => $this->text['ar'] ?? null,
            'count' => $this->count,
            'reward' => $this->getLocalizedReward($locale),
            'source' => $this->source,
            'audio_url' => $this->audio_url,
            'category_key' => $this->category_key,
            'category' => $this->category ? [
                'id' => $this->category->id,
                'name' => $this->category->getTranslation('name', $locale ?? 'en'),
            ] : null,
        ];
    }
}
