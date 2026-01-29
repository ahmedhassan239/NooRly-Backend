<?php

namespace App\Domain\QuranAllLang\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Translation Model
 * 
 * Represents a Quran translation/edition in the quran_all_lang database.
 * 
 * Relationships:
 * - BelongsTo: Language (each translation belongs to one language)
 * - HasMany: VerseText (one translation has many verse texts)
 * 
 * @property int $id
 * @property int $language_id
 * @property string $source_name Translator/edition name (e.g., 'Original', 'Sahih International')
 * @property string $file_name Original CSV filename for reference
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read Language $language
 * @property-read \Illuminate\Database\Eloquent\Collection|VerseText[] $verseTexts
 */
class Translation extends Model
{
    protected $connection = 'mysql_quran_all_lang';
    protected $table = 'translations';
    protected $guarded = [];

    protected $casts = [
        'language_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['language']; // Eager load to avoid N+1

    /**
     * Get the language this translation belongs to.
     */
    public function language(): BelongsTo
    {
        return $this->belongsTo(Language::class, 'language_id');
    }

    /**
     * Get all verse texts for this translation.
     */
    public function verseTexts(): HasMany
    {
        return $this->hasMany(VerseText::class, 'translation_id');
    }

    /**
     * Get a sample of verse texts (first 5).
     */
    public function sampleVerseTexts(): HasMany
    {
        return $this->hasMany(VerseText::class, 'translation_id')->limit(5);
    }

    /**
     * Scope to filter by language.
     */
    public function scopeByLanguage(Builder $query, int $languageId): Builder
    {
        return $query->where('language_id', $languageId);
    }

    /**
     * Scope to filter by language code.
     */
    public function scopeByLanguageCode(Builder $query, string $code): Builder
    {
        return $query->whereHas('language', function (Builder $q) use ($code) {
            $q->where('code', $code);
        });
    }

    /**
     * Scope to search by source name.
     */
    public function scopeSearchBySource(Builder $query, string $term): Builder
    {
        return $query->where('source_name', 'like', "%{$term}%");
    }

    /**
     * Get the full display name (Language - Source).
     */
    public function getFullNameAttribute(): string
    {
        return "{$this->language->name} - {$this->source_name}";
    }
}
