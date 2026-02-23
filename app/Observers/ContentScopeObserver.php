<?php

namespace App\Observers;

use App\Domain\ContentScopes\ContentScope;
use Illuminate\Support\Facades\Cache;

/**
 * Clears active content scopes cache when a scope is created, updated, or deleted
 * so the API always serves fresh data after admin changes.
 */
class ContentScopeObserver
{
    public const CACHE_KEY = 'active_content_scopes';
    public const CACHE_TTL_SECONDS = 600; // 10 minutes

    public function created(ContentScope $contentScope): void
    {
        $this->clearCache();
    }

    public function updated(ContentScope $contentScope): void
    {
        $this->clearCache();
    }

    public function deleted(ContentScope $contentScope): void
    {
        $this->clearCache();
    }

    protected function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
