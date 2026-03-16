<?php

namespace App\Domain\Notifications;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class NotificationTemplate extends Model
{
    protected $table = 'notification_templates';

    protected $fillable = [
        'key',
        'category',
        'sub_type',
        'locale',
        'title',
        'body',
        'cta',
        'variables',
        'priority',
        'is_active',
        'variation_group',
        'sort_order',
    ];

    protected $casts = [
        'variables' => 'array',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeForLocale(Builder $query, string $locale): Builder
    {
        return $query->where('locale', $locale);
    }

    public function scopeForCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function scopeForSubType(Builder $query, string $subType): Builder
    {
        return $query->where('sub_type', $subType);
    }
}
