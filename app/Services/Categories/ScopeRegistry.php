<?php

namespace App\Services\Categories;

use App\Domain\ContentScopes\ContentScope;
use Illuminate\Support\Facades\Cache;

/**
 * ScopeRegistry Service
 * 
 * Provides cached access to content scopes to avoid repeated queries
 * and prevent hardcoding scope IDs across the application.
 */
class ScopeRegistry
{
    protected const CACHE_KEY_PREFIX = 'content_scope:';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get scope ID by key.
     * 
     * @param string $key Scope key (e.g., 'lessons', 'duas')
     * @return int|null Scope ID or null if not found
     */
    public function idFor(string $key): ?int
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'id:' . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            $scope = ContentScope::where('key', $key)->first();
            return $scope?->id;
        });
    }

    /**
     * Get scope by key.
     * 
     * @param string $key Scope key
     * @return ContentScope|null
     */
    public function scopeFor(string $key): ?ContentScope
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'scope:' . $key;
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($key) {
            return ContentScope::where('key', $key)->first();
        });
    }

    /**
     * Get scope for a model class.
     * 
     * @param string $modelClass Full model class name (e.g., 'App\\Domain\\Lessons\\Lesson')
     * @return ContentScope|null
     */
    public function scopeForModel(string $modelClass): ?ContentScope
    {
        $cacheKey = self::CACHE_KEY_PREFIX . 'model:' . md5($modelClass);
        
        return Cache::remember($cacheKey, self::CACHE_TTL, function () use ($modelClass) {
            return ContentScope::where('model_class', $modelClass)
                ->where('is_active', true)
                ->first();
        });
    }

    /**
     * Get scope ID for a model class.
     * 
     * @param string $modelClass Full model class name
     * @return int|null Scope ID or null if not found
     */
    public function idForModel(string $modelClass): ?int
    {
        $scope = $this->scopeForModel($modelClass);
        return $scope?->id;
    }

    /**
     * Clear cache for a specific scope key.
     * 
     * @param string $key Scope key
     */
    public function clearCache(string $key): void
    {
        Cache::forget(self::CACHE_KEY_PREFIX . 'id:' . $key);
        Cache::forget(self::CACHE_KEY_PREFIX . 'scope:' . $key);
    }

    /**
     * Clear all scope caches.
     */
    public function clearAllCache(): void
    {
        // Clear all cache entries with the prefix
        // Note: This is a simple implementation. For production, consider using cache tags.
        $scopes = ContentScope::all();
        foreach ($scopes as $scope) {
            $this->clearCache($scope->key);
            if ($scope->model_class) {
                Cache::forget(self::CACHE_KEY_PREFIX . 'model:' . md5($scope->model_class));
            }
        }
    }
}
