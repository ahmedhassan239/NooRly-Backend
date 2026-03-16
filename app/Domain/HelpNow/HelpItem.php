<?php

namespace App\Domain\HelpNow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HelpItem extends Model
{
    protected $table = 'help_items';

    protected $fillable = [
        'category_id',
        'slug',
        'sort_order',
        'is_active',
        'title_en',
        'title_ar',
        'subtitle_en',
        'subtitle_ar',
        'content_en',
        'content_ar',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    public function getTitleForLocale(string $locale): string
    {
        return $locale === 'ar' ? $this->title_ar : $this->title_en;
    }

    public function getSubtitleForLocale(string $locale): ?string
    {
        if ($locale === 'ar') {
            return $this->subtitle_ar;
        }
        return $this->subtitle_en;
    }

    public function getContentForLocale(string $locale): string
    {
        return $locale === 'ar' ? $this->content_ar : $this->content_en;
    }

    public function toApiArray(string $locale = 'en'): array
    {
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'category_id' => $this->category_id,
            'category_slug' => $this->category?->slug,
            'title' => $this->getTitleForLocale($locale),
            'subtitle' => $this->getSubtitleForLocale($locale),
            'content' => $this->getContentForLocale($locale),
            'sort_order' => $this->sort_order,
        ];
    }
}
