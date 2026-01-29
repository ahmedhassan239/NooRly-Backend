<?php

namespace App\Domain\QuranAllLang\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * VerseText Model
 * 
 * Represents the translated text of a Quran verse in a specific translation.
 * 
 * Relationships:
 * - BelongsTo: QuranVerse (each text belongs to one verse)
 * - BelongsTo: Translation (each text belongs to one translation)
 * 
 * @property int $id
 * @property int $verse_id
 * @property int $translation_id
 * @property string $text The translated verse text
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read QuranVerse $verse
 * @property-read Translation $translation
 */
class VerseText extends Model
{
    protected $connection = 'mysql_quran_all_lang';
    protected $table = 'verse_texts';
    protected $guarded = [];

    protected $casts = [
        'verse_id' => 'integer',
        'translation_id' => 'integer',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $with = ['translation.language']; // Eager load to avoid N+1

    /**
     * Get the verse this text belongs to.
     */
    public function verse(): BelongsTo
    {
        return $this->belongsTo(QuranVerse::class, 'verse_id');
    }

    /**
     * Get the translation this text belongs to.
     */
    public function translation(): BelongsTo
    {
        return $this->belongsTo(Translation::class, 'translation_id');
    }

    /**
     * Scope to filter by verse.
     */
    public function scopeByVerse(Builder $query, int $verseId): Builder
    {
        return $query->where('verse_id', $verseId);
    }

    /**
     * Scope to filter by translation.
     */
    public function scopeByTranslation(Builder $query, int $translationId): Builder
    {
        return $query->where('translation_id', $translationId);
    }

    /**
     * Scope to filter by language.
     */
    public function scopeByLanguage(Builder $query, int $languageId): Builder
    {
        return $query->whereHas('translation', function (Builder $q) use ($languageId) {
            $q->where('language_id', $languageId);
        });
    }

    /**
     * Scope to search by text content.
     */
    public function scopeSearchText(Builder $query, string $term): Builder
    {
        return $query->where('text', 'like', "%{$term}%");
    }

    /**
     * Get the text with proper direction based on language.
     */
    public function getDirectedTextAttribute(): array
    {
        return [
            'text' => $this->text,
            'direction' => $this->translation->language->direction ?? 'ltr',
        ];
    }
}
