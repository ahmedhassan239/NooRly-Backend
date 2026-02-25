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
     * Normalize text/reward array to ar/en with null-safe empty string.
     */
    private function normalizeContentArray(?array $value): array
    {
        if ($value === null || !is_array($value)) {
            return ['ar' => '', 'en' => ''];
        }
        return [
            'ar' => (string) ($value['ar'] ?? ''),
            'en' => (string) ($value['en'] ?? ''),
        ];
    }

    /**
     * Convert to API format (normalized structure, null-safe).
     * Backward compatible: reads text/reward from DB; title no longer used.
     */
    public function toApiArray(?string $locale = null): array
    {
        $text = $this->normalizeContentArray($this->text);
        $reward = $this->normalizeContentArray($this->reward);

        $payload = [
            'id' => $this->id,
            'type' => 'adhkar',
            'repeat_count' => (int) $this->count,
            'source' => (string) ($this->source ?? ''),
            'content' => [
                'text' => $text,
                'reward' => $reward,
            ],
        ];

        $payload['category_id'] = $this->category_id;

        if ($this->relationLoaded('category') && $this->category) {
            $payload['category'] = [
                'id' => $this->category->id,
                'name' => $this->category->getName($locale ?? 'en'),
                'slug' => $this->category->getSlug($locale ?? 'en'),
            ];
        }

        return $payload;
    }
}
