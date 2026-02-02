<?php

namespace App\Domain\QuranAllLang\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * QuranVerse Model
 * 
 * Represents a Quran verse in the quran_all_lang database.
 * 
 * Relationships:
 * - HasMany: VerseText (one verse has many translations)
 * 
 * @property int $id
 * @property int $surah_number Surah number (1-114)
 * @property int $ayah_number Ayah number within the surah
 * @property string $ayah_key Unique identifier in format "surah:ayah" (e.g., "1:1")
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property-read \Illuminate\Database\Eloquent\Collection|VerseText[] $verseTexts
 */
class QuranVerse extends Model
{
    protected $connection = 'mysql_quran_all_lang';
    protected $table = 'quran_verses';
    protected $guarded = [];

    public $timestamps = false; // Only created_at exists in schema

    protected $casts = [
        'surah_number' => 'integer',
        'ayah_number' => 'integer',
        'created_at' => 'datetime',
    ];

    /**
     * Get all verse texts (translations) for this verse.
     */
    public function verseTexts(): HasMany
    {
        return $this->hasMany(VerseText::class, 'verse_id');
    }

    /**
     * Get verse texts for active languages only, ordered by priority.
     * This is the SINGLE SOURCE OF TRUTH for active verse texts.
     * 
     * Returns a collection with relationships loaded.
     */
    public function activeVerseTexts()
    {
        $verseTexts = $this->verseTexts()
            ->orderByLanguagePriority()
            ->get();
        
        // Load relationships manually (cannot use ->with() after JOINs)
        $verseTexts->load('translation.language');
        
        return $verseTexts;
    }


    /**
     * Get the surah name.
     */
    public function getSurahNameAttribute(): string
    {
        return \App\Domain\QuranAllLang\Helpers\SurahHelper::getName($this->surah_number);
    }

    /**
     * Scope to filter by surah number.
     */
    public function scopeBySurah(Builder $query, int $surahNumber): Builder
    {
        return $query->where('surah_number', $surahNumber);
    }

    /**
     * Scope to filter by ayah number.
     */
    public function scopeByAyah(Builder $query, int $ayahNumber): Builder
    {
        return $query->where('ayah_number', $ayahNumber);
    }

    /**
     * Scope to filter by ayah key (e.g., "1:1").
     */
    public function scopeByAyahKey(Builder $query, string $ayahKey): Builder
    {
        return $query->where('ayah_key', $ayahKey);
    }

    /**
     * Scope to filter by surah and ayah number.
     */
    public function scopeBySurahAndAyah(Builder $query, int $surahNumber, int $ayahNumber): Builder
    {
        return $query->where('surah_number', $surahNumber)
                     ->where('ayah_number', $ayahNumber);
    }

    /**
     * Scope to search verse texts.
     */
    public function scopeSearchText(Builder $query, string $term): Builder
    {
        return $query->whereHas('verseTexts', function (Builder $q) use ($term) {
            $q->where('text', 'like', "%{$term}%")
              ->forActiveLanguages();
        });
    }

    /**
     * Get the full reference as "Surah Name, Ayah Y".
     */
    public function getFullReferenceAttribute(): string
    {
        return "{$this->surah_name}, Ayah {$this->ayah_number}";
    }

    /**
     * Get the short reference as "X:Y".
     */
    public function getShortReferenceAttribute(): string
    {
        return "{$this->surah_number}:{$this->ayah_number}";
    }
}
