<?php

namespace App\Domain\QuranAllLang\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Language Model
 * 
 * Represents a language available in the quran_all_lang database.
 * 
 * Relationships:
 * - HasMany: Translation (one language has many translations)
 * 
 * @property int $id
 * @property string $code Language code (e.g., 'ar', 'en', 'bn')
 * @property string $name Language name (e.g., 'Arabic', 'English')
 * @property bool $is_rtl Whether this is a right-to-left language
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class Language extends Model
{
    protected $connection = 'mysql_quran_all_lang';
    protected $table = 'languages';
    protected $guarded = [];

    protected $casts = [
        'is_rtl' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get all translations for this language.
     */
    public function translations(): HasMany
    {
        return $this->hasMany(Translation::class, 'language_id');
    }

    /**
     * Scope to filter RTL languages only.
     */
    public function scopeRtl(Builder $query): Builder
    {
        return $query->where('is_rtl', true);
    }

    /**
     * Scope to filter LTR languages only.
     */
    public function scopeLtr(Builder $query): Builder
    {
        return $query->where('is_rtl', false);
    }

    /**
     * Scope to find language by code.
     */
    public function scopeByCode(Builder $query, string $code): Builder
    {
        return $query->where('code', $code);
    }

    /**
     * Scope to filter active languages only.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Scope to filter inactive languages only.
     */
    public function scopeInactive(Builder $query): Builder
    {
        return $query->where('is_active', false);
    }

    /**
     * Get the direction attribute (rtl or ltr).
     */
    public function getDirectionAttribute(): string
    {
        return $this->is_rtl ? 'rtl' : 'ltr';
    }
}
