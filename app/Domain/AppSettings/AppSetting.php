<?php

namespace App\Domain\AppSettings;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * AppSetting Model
 * 
 * Stores application-wide settings that can be managed from Filament dashboard.
 * Settings can be public (exposed via API) or private (internal use only).
 * 
 * @property int $id
 * @property string $key Unique setting key
 * @property mixed $value Setting value (stored as JSON)
 * @property string $group Setting group for organization
 * @property string $type Value type: string, boolean, integer, json, array
 * @property string|null $description Human-readable description
 * @property bool $is_public Whether to expose via public API
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'description',
        'is_public',
    ];

    protected $casts = [
        'value' => 'json',
        'is_public' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Cache key prefix for settings
     */
    protected const CACHE_PREFIX = 'app_setting:';
    protected const CACHE_TTL = 3600; // 1 hour

    /**
     * Get a setting value by key
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return Cache::remember(
            self::CACHE_PREFIX . $key,
            self::CACHE_TTL,
            function () use ($key, $default) {
                $setting = static::where('key', $key)->first();
                return $setting ? $setting->typed_value : $default;
            }
        );
    }

    /**
     * Set a setting value by key
     */
    public static function set(string $key, mixed $value, ?string $group = null): static
    {
        $setting = static::updateOrCreate(
            ['key' => $key],
            [
                'value' => $value,
                'group' => $group ?? 'general',
            ]
        );

        Cache::forget(self::CACHE_PREFIX . $key);
        Cache::forget('app_settings:public');

        return $setting;
    }

    /**
     * Get all public settings (for API)
     */
    public static function getPublicSettings(): array
    {
        return Cache::remember('app_settings:public', self::CACHE_TTL, function () {
            return static::where('is_public', true)
                ->get()
                ->mapWithKeys(function ($setting) {
                    return [$setting->key => $setting->typed_value];
                })
                ->toArray();
        });
    }

    /**
     * Get all settings by group
     */
    public static function getByGroup(string $group): array
    {
        return static::where('group', $group)
            ->get()
            ->mapWithKeys(function ($setting) {
                return [$setting->key => $setting->typed_value];
            })
            ->toArray();
    }

    /**
     * Get the properly typed value
     */
    public function getTypedValueAttribute(): mixed
    {
        $value = $this->value;

        return match ($this->type) {
            'boolean' => (bool) $value,
            'integer' => (int) $value,
            'array' => is_array($value) ? $value : [],
            'json' => $value,
            default => $value,
        };
    }

    /**
     * Scope to filter public settings
     */
    public function scopePublic(Builder $query): Builder
    {
        return $query->where('is_public', true);
    }

    /**
     * Scope to filter by group
     */
    public function scopeByGroup(Builder $query, string $group): Builder
    {
        return $query->where('group', $group);
    }

    /**
     * Clear all settings cache
     */
    public static function clearCache(): void
    {
        Cache::forget('app_settings:public');
        
        static::all()->each(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
        });
    }

    /**
     * Boot method to clear cache on save
     */
    protected static function booted(): void
    {
        static::saved(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget('app_settings:public');
        });

        static::deleted(function ($setting) {
            Cache::forget(self::CACHE_PREFIX . $setting->key);
            Cache::forget('app_settings:public');
        });
    }
}
