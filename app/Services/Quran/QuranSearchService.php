<?php

namespace App\Services\Quran;

use App\Contracts\QuranSearchServiceInterface;
use App\Domain\QuranAllLang\Helpers\QuranVerseLabel;
use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Quran Search Service
 * 
 * Provides search functionality for Quran verses using Arabic text.
 * Searches in the verse_texts table for Arabic translations.
 * 
 * DIACRITIC-AGNOSTIC SEARCH:
 * Uses the text_normalized column for searching, which allows:
 * - Searching "بقرة" to match "بَقَرَةً" (with tashkeel)
 * - Ignoring tatweel and other decorative characters
 * - Matching regardless of Alef/Yaa variant forms
 * 
 * PERFORMANCE:
 * - LIKE %term% queries cannot use B-tree indexes efficiently
 * - A FULLTEXT index is added for potential future optimization
 * - Results are limited and ordered by surah/ayah for predictable UX
 */
class QuranSearchService implements QuranSearchServiceInterface
{
    /**
     * The database connection for Quran data.
     */
    private const CONNECTION = 'mysql_quran_all_lang';

    /**
     * Maximum text length for labels.
     */
    private const MAX_LABEL_LENGTH = 80;

    /**
     * Context characters to show around the match in preview.
     */
    private const CONTEXT_CHARS = 30;

    /**
     * Search for Quran verses by Arabic text.
     * 
     * Searches the text_normalized column for diacritic-agnostic matching.
     * The search term is normalized before querying, so "بقرة" matches "بَقَرَةً".
     * 
     * @param string $term The Arabic search term (with or without diacritics)
     * @param int $limit Maximum number of results to return
     * @return array<int, string> Array of [verse_id => label] pairs
     */
    public function searchArabicVerses(string $term, int $limit = 25): array
    {
        $term = trim($term);
        if (empty($term) || mb_strlen($term) < 2) {
            return [];
        }

        // Normalize the search term (removes diacritics, normalizes characters)
        $normalizedTerm = ArabicTextNormalizer::prepareSearchTerm($term);
        
        // Search the normalized column for diacritic-agnostic matching
        $results = DB::connection(self::CONNECTION)
            ->table('verse_texts')
            ->join('quran_verses', 'verse_texts.verse_id', '=', 'quran_verses.id')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('languages.code', 'ar') // Only Arabic text
            ->where('verse_texts.text_normalized', 'LIKE', "%{$normalizedTerm}%")
            ->select([
                'quran_verses.id as verse_id',
                'quran_verses.surah_number',
                'quran_verses.ayah_number',
                'quran_verses.ayah_key',
                'verse_texts.text',
                'verse_texts.text_normalized',
            ])
            ->orderBy('quran_verses.surah_number')
            ->orderBy('quran_verses.ayah_number')
            ->limit($limit)
            ->get();

        $byText = $results->mapWithKeys(function ($row) use ($normalizedTerm) {
            $snippet = $this->optionalSnippet($row->text, $row->text_normalized, $normalizedTerm);
            $textForLabel = $snippet !== '' ? $snippet : $row->text;
            $label = QuranVerseLabel::formatForAdmin(
                (int) $row->surah_number,
                (int) $row->ayah_number,
                $textForLabel,
                self::MAX_LABEL_LENGTH
            );
            return [$row->verse_id => $label];
        })->toArray();

        // Also search by surah name (EN/AR) so "Al-Imran" or "آل عمران" finds verses
        $surahNumber = SurahHelper::findSurahNumberByName($term);
        if ($surahNumber !== null) {
            $bySurah = DB::connection(self::CONNECTION)
                ->table('quran_verses')
                ->where('surah_number', $surahNumber)
                ->orderBy('surah_number')
                ->orderBy('ayah_number')
                ->limit($limit)
                ->get();
            foreach ($bySurah as $row) {
                $id = $row->id;
                if (!isset($byText[$id])) {
                    $byText[$id] = QuranVerseLabel::formatForAdmin(
                        (int) $row->surah_number,
                        (int) $row->ayah_number,
                        '',
                        null
                    );
                }
            }
        }

        return array_slice($byText, 0, $limit, true);
    }

    /**
     * Optionally return a short snippet around the search match for context.
     */
    private function optionalSnippet(string $text, string $textNormalized, string $normalizedTerm): string
    {
        $pos = mb_stripos($textNormalized, $normalizedTerm);
        if ($pos === false) {
            return '';
        }
        return $this->extractSnippetAroundMatch($text, $pos, mb_strlen($normalizedTerm));
    }

    /**
     * Get labels for a list of verse IDs.
     * Label format: "(Arabic surah name: ayah_number) ayah Arabic text" for admin select/chips.
     *
     * @param array<int> $ids Array of verse IDs
     * @return array<int, string> Array of [verse_id => label] pairs
     */
    public function getVerseLabels(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $verses = DB::connection(self::CONNECTION)
            ->table('quran_verses')
            ->whereIn('quran_verses.id', $ids)
            ->select([
                'quran_verses.id as verse_id',
                'quran_verses.surah_number',
                'quran_verses.ayah_number',
            ])
            ->get();

        // One query to load Arabic text per verse (first Arabic translation per verse)
        $textsByVerse = DB::connection(self::CONNECTION)
            ->table('verse_texts')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->where('languages.code', 'ar')
            ->whereIn('verse_texts.verse_id', $ids)
            ->select('verse_texts.verse_id', 'verse_texts.text')
            ->get()
            ->groupBy('verse_id')
            ->map(fn ($rows) => $rows->first()->text ?? '');

        return $verses->mapWithKeys(function ($row) use ($textsByVerse) {
            $text = $textsByVerse->get($row->verse_id, '');
            $label = QuranVerseLabel::formatForAdmin(
                (int) $row->surah_number,
                (int) $row->ayah_number,
                $text,
                self::MAX_LABEL_LENGTH
            );
            return [$row->verse_id => $label];
        })->toArray();
    }

    /**
     * Extract a snippet of text around the matched position.
     * 
     * @param string $text The full text (original with diacritics)
     * @param int $matchPos Approximate position of the match
     * @param int $termLength Length of the search term
     * @return string The snippet with context
     */
    private function extractSnippetAroundMatch(string $text, int $matchPos, int $termLength): string
    {
        $textLength = mb_strlen($text);
        
        // Adjust position accounting for possible diacritic differences
        // The match position is from normalized text, so actual position may be slightly different
        // We'll use it as approximate and show context around it
        $adjustedPos = min($matchPos, max(0, $textLength - $termLength - self::CONTEXT_CHARS));
        
        // Calculate start position (with context before the match)
        $start = max(0, $adjustedPos - self::CONTEXT_CHARS);
        
        // Calculate total length to extract
        $length = self::CONTEXT_CHARS * 2 + $termLength + 20; // Extra for diacritics
        
        // Make sure we don't exceed text length
        $length = min($length, $textLength - $start);
        
        // Extract the snippet
        $snippet = mb_substr($text, $start, $length);
        
        // Add ellipsis if we're not at the start/end
        $prefix = $start > 0 ? '…' : '';
        $suffix = ($start + $length) < $textLength ? '…' : '';
        
        return $prefix . trim($snippet) . $suffix;
    }
}
