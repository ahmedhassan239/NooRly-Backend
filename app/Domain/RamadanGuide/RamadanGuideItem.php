<?php

namespace App\Domain\RamadanGuide;

use App\Support\Ramadan\RamadanIconRegistry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RamadanGuideItem extends Model
{
    protected $table = 'ramadan_guide_items';

    protected $fillable = [
        'slug',
        'sort_order',
        'icon',
        'is_active',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
        'content_en',
        'content_ar',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

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

    public function getDescriptionForLocale(string $locale): string
    {
        return $locale === 'ar' ? $this->description_ar : $this->description_en;
    }

    public function getContentForLocale(string $locale): string
    {
        return $locale === 'ar' ? $this->content_ar : $this->content_en;
    }

    public function toApiArray(string $locale = 'en'): array
    {
        $iconKey = RamadanIconRegistry::canonicalizeStoredKey($this->icon);

        // icon: legacy key for older mobile clients; prefer icon_key + icon_url.
        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTitleForLocale($locale),
            'description' => $this->getDescriptionForLocale($locale),
            'content' => $this->getContentForLocale($locale),
            'icon' => $iconKey,
            'icon_key' => $iconKey,
            'icon_url' => RamadanIconRegistry::urlForStoredIcon($this->icon),
            'sort_order' => $this->sort_order,
        ];
    }
}
