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
 * ARABIC SEARCH:
 * The text_normalized column stores Arabic text with diacritics removed for search.
 * This allows searching "بقرة" to match "بَقَرَةً" (with tashkeel).
 * The column is automatically populated via VerseTextObserver.
 * 
 * @property int $id
 * @property int $verse_id
 * @property int $translation_id
 * @property string $text The translated verse text (may include diacritics)
 * @property string|null $text_normalized Normalized text without diacritics for search
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

    // Removed protected $with to avoid conflicts with JOIN-based scopes
    // Use ->with(['translation.language']) explicitly when needed

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
     * Scope to filter by active languages only.
     */
    public function scopeForActiveLanguages(Builder $query): Builder
    {
        return $query->whereHas('translation', function (Builder $q) {
            $q->whereHas('language', function (Builder $subQ) {
                $subQ->where('is_active', true);
            });
        });
    }

    /**
     * Scope to order by language priority: English first, then Arabic, then others.
     * This scope also filters by active languages.
     * 
     * IMPORTANT: This scope uses JOINs. When using this scope, load relationships
     * manually after get() using ->load() to avoid query conflicts.
     */
    public function scopeOrderByLanguagePriority(Builder $query): Builder
    {
        return $query->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('languages.is_active', true)
            ->select('verse_texts.*', 'languages.code as language_code')
            ->orderByRaw("CASE WHEN languages.code = 'en' THEN 1 WHEN languages.code = 'ar' THEN 2 ELSE 3 END");
    }

    /**
     * Scope to search by text content.
     * Uses the original text column (exact match with diacritics).
     */
    public function scopeSearchText(Builder $query, string $term): Builder
    {
        return $query->where('text', 'like', "%{$term}%");
    }

    /**
     * Scope to search by normalized text (diacritic-agnostic).
     * Normalizes the search term and searches the text_normalized column.
     * 
     * This allows searching "بقرة" to match "بَقَرَةً" (with tashkeel).
     */
    public function scopeSearchNormalized(Builder $query, string $term): Builder
    {
        $normalizedTerm = \App\Support\Arabic\ArabicTextNormalizer::normalize($term);
        return $query->where('text_normalized', 'like', "%{$normalizedTerm}%");
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
