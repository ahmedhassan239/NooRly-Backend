<?php

namespace App\Domain\HelpNow;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;

class HelpCategory extends Model
{
    protected $table = 'help_categories';

    protected $fillable = [
        'slug',
        'sort_order',
        'icon',
        'is_active',
        'title_en',
        'title_ar',
        'description_en',
        'description_ar',
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(HelpItem::class, 'category_id');
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

    public function getDescriptionForLocale(string $locale): ?string
    {
        if ($locale === 'ar') {
            return $this->description_ar;
        }
        return $this->description_en;
    }

    public function toApiArray(string $locale = 'en'): array
    {
        $items = $this->items()
            ->active()
            ->ordered()
            ->get()
            ->map(fn (HelpItem $item) => $item->toApiArray($locale));

        return [
            'id' => $this->id,
            'slug' => $this->slug,
            'title' => $this->getTitleForLocale($locale),
            'description' => $this->getDescriptionForLocale($locale),
            'icon' => $this->icon,
            'sort_order' => $this->sort_order,
            'items' => $items->values()->all(),
        ];
    }
}
