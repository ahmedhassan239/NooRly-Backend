<?php

namespace App\Domain\Notifications\Services;

use App\Domain\Notifications\NotificationTemplate;
use Illuminate\Support\Collection;

class NotificationTemplateService
{
    /**
     * Resolve a single template by key and locale.
     * Falls back to 'en' if the requested locale doesn't exist.
     */
    public function resolve(string $key, string $locale = 'en'): ?NotificationTemplate
    {
        $template = NotificationTemplate::query()
            ->active()
            ->where('key', $key)
            ->where('locale', $locale)
            ->first();

        if (! $template && $locale !== 'en') {
            $template = NotificationTemplate::query()
                ->active()
                ->where('key', $key)
                ->where('locale', 'en')
                ->first();
        }

        return $template;
    }

    /**
     * Pick a random variation from a variation_group + sub_type + locale.
     */
    public function randomVariation(string $subType, string $locale = 'en'): ?NotificationTemplate
    {
        $templates = NotificationTemplate::query()
            ->active()
            ->forSubType($subType)
            ->forLocale($locale)
            ->get();

        if ($templates->isEmpty() && $locale !== 'en') {
            $templates = NotificationTemplate::query()
                ->active()
                ->forSubType($subType)
                ->forLocale('en')
                ->get();
        }

        return $templates->isNotEmpty() ? $templates->random() : null;
    }

    /**
     * Get all templates for a category and locale.
     */
    public function forCategory(string $category, string $locale = 'en'): Collection
    {
        return NotificationTemplate::query()
            ->active()
            ->forCategory($category)
            ->forLocale($locale)
            ->orderBy('sort_order')
            ->get();
    }
}
