<?php

namespace App\Models\Domain\Language;

use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    protected $fillable = [
        'code',
        'name',
        'native_name',
        'direction',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    /**
     * Boot the model and enforce single default language.
     */
    protected static function booted()
    {
        // Before saving, if setting as default, unset all others
        static::saving(function ($language) {
            if ($language->is_default && $language->isDirty('is_default')) {
                static::where('id', '!=', $language->id)->update(['is_default' => false]);
            }
        });
    }

    /**
     * Scope to get only active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Get the default language.
     */
    public static function getDefault()
    {
        return static::where('is_default', true)->first() ?? static::where('code', 'en')->first();
    }

    /**
     * Get language by code, or return default.
     */
    public static function resolve(?string $code): Language
    {
        if (!$code) {
            return static::getDefault();
        }

        return static::active()->where('code', $code)->first() ?? static::getDefault();
    }
}
