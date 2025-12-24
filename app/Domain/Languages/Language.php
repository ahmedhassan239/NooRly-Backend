<?php

namespace App\Domain\Languages;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Language extends Model
{
    use HasFactory;

    /**
     * Scope a query to only include active languages.
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Resolve language by code or fallback to default.
     */
    public static function resolve(?string $code): self
    {
        if ($code) {
            $language = self::active()->where('code', $code)->first();
            if ($language) {
                return $language;
            }
        }

        // Try default language
        $default = self::active()->where('is_default', true)->first();
        if ($default) {
            return $default;
        }

        // Final fallback (English)
        // If English is not in DB, we'll return a dynamic instance to avoid crash
        // but normally English should be there.
        return self::where('code', 'en')->first() ?? new self([
            'code' => 'en',
            'name' => 'English',
            'direction' => 'ltr',
        ]);
    }

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
}
