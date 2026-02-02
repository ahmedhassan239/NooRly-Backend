<?php

namespace App\Services\Quran;

use App\Contracts\QuranSearchServiceInterface;
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

        return $results->mapWithKeys(function ($row) use ($normalizedTerm) {
            return [
                $row->verse_id => $this->formatVerseLabelWithMatch(
                    $row->ayah_key,
                    $row->text,
                    $row->text_normalized,
                    $normalizedTerm
                ),
            ];
        })->toArray();
    }

    /**
     * Get labels for a list of verse IDs.
     * 
     * @param array<int> $ids Array of verse IDs
     * @return array<int, string> Array of [verse_id => label] pairs
     */
    public function getVerseLabels(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        $results = DB::connection(self::CONNECTION)
            ->table('verse_texts')
            ->join('quran_verses', 'verse_texts.verse_id', '=', 'quran_verses.id')
            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
            ->join('languages', 'translations.language_id', '=', 'languages.id')
            ->whereIn('quran_verses.id', $ids)
            ->where('languages.code', 'ar') // Only Arabic text
            ->select([
                'quran_verses.id as verse_id',
                'quran_verses.ayah_key',
                'verse_texts.text',
            ])
            ->get();

        return $results->mapWithKeys(function ($row) {
            return [
                $row->verse_id => $this->formatVerseLabel(
                    $row->ayah_key,
                    $row->text
                ),
            ];
        })->toArray();
    }

    /**
     * Format a verse label for display (basic truncation).
     * 
     * @param string $ayahKey The verse reference (e.g., "2:255")
     * @param string $text The Arabic verse text
     * @return string The formatted label
     */
    private function formatVerseLabel(string $ayahKey, string $text): string
    {
        $truncatedText = Str::limit($text, self::MAX_LABEL_LENGTH, '…');
        return "({$ayahKey}) {$truncatedText}";
    }

    /**
     * Format a verse label with match highlighting (shows relevant snippet).
     * 
     * @param string $ayahKey The verse reference (e.g., "2:255")
     * @param string $text The original Arabic verse text (with diacritics)
     * @param string $textNormalized The normalized text (for finding match position)
     * @param string $normalizedTerm The normalized search term
     * @return string The formatted label with relevant snippet
     */
    private function formatVerseLabelWithMatch(
        string $ayahKey, 
        string $text, 
        string $textNormalized,
        string $normalizedTerm
    ): string {
        $prefix = "({$ayahKey}) ";
        
        // Find the position of the search term in the normalized text
        $pos = mb_stripos($textNormalized, $normalizedTerm);
        
        if ($pos !== false) {
            // Extract a snippet around the match from the ORIGINAL text
            // The positions should roughly correspond between normalized and original
            $snippet = $this->extractSnippetAroundMatch($text, $pos, mb_strlen($normalizedTerm));
            return $prefix . $snippet;
        }
        
        // Fallback: just truncate from start
        return $prefix . Str::limit($text, self::MAX_LABEL_LENGTH, '…');
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
