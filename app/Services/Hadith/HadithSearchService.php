<?php

namespace App\Services\Hadith;

use App\Contracts\HadithSearchServiceInterface;
use App\Support\Arabic\ArabicTextNormalizer;
use Illuminate\Support\Facades\DB;

/**
 * Hadith Search Service
 *
 * Provides search functionality for Hadith items using Arabic text.
 */
class HadithSearchService implements HadithSearchServiceInterface
{
    /**
     * Maximum text length for labels.
     */
    private const MAX_LABEL_LENGTH = 100;

    /**
     * Context characters to show around the match.
     */
    private const CONTEXT_CHARS = 40;

    /**
     * Current search term for highlighting.
     */
    private ?string $currentSearchTerm = null;

    /**
     * Get the database connection name for Hadith data.
     */
    private function connection(): string
    {
        return config('content_sources.hadith.connection', env('DB_HADITH_CONNECTION', 'mysql_hadith'));
    }

    /**
     * Get the qualified table name for hadiths (db.table).
     */
    private function table(): string
    {
        return config(
            'content_sources.hadith.table',
            env('HADITH_TABLE_QUALIFIED', 'all_hadiths_clean.hadiths')
        );
    }

    /**
     * Search for Hadith items by Arabic text.
     *
     * @param string $term The Arabic search term
     * @param int $limit Maximum number of results to return
     * @return array<int, string> Array of [hadith_id => label] pairs
     */
    public function searchArabicHadith(string $term, int $limit = 25): array
    {
        $term = trim($term);
        if (empty($term) || mb_strlen($term) < 2) {
            return [];
        }

        $this->currentSearchTerm = $term;

        // Normalize the search term for flexible matching
        $normalizedTerm = ArabicTextNormalizer::prepareSearchTerm($term);

        // Get column names from config
        $textColumn = config('content_sources.hadith.columns.text_ar', 'text_ar');
        $sourceColumn = config('content_sources.hadith.columns.collection', 'source');
        $hadithNoColumn = config('content_sources.hadith.columns.hadith_number', 'hadith_no');

        // Build query with both original and normalized search
        $results = DB::connection($this->connection())
            ->table($this->table())
            ->where(function ($query) use ($term, $normalizedTerm, $textColumn) {
                // Search with original term (handles exact matches)
                $query->where($textColumn, 'LIKE', "%{$term}%");

                // Also search with normalized term if different
                if ($normalizedTerm !== $term) {
                    $query->orWhere($textColumn, 'LIKE', "%{$normalizedTerm}%");
                }
            })
            ->select([
                'id',
                $sourceColumn . ' as source',
                $hadithNoColumn . ' as hadith_no',
                $textColumn . ' as text_ar',
            ])
            // Order by position of match (earlier match = more relevant)
            ->orderByRaw("LOCATE(?, {$textColumn})", [$term])
            ->orderBy('id')
            ->limit($limit)
            ->get();

        return $results->mapWithKeys(function ($row) use ($term, $normalizedTerm) {
            return [
                $row->id => $this->formatHadithLabelWithMatch(
                    $row->source,
                    $row->hadith_no,
                    $row->text_ar,
                    $term,
                    $normalizedTerm
                ),
            ];
        })->toArray();
    }

    /**
     * Get labels for a list of hadith IDs.
     *
     * @param array<int> $ids Array of hadith IDs
     * @return array<int, string> Array of [hadith_id => label] pairs
     */
    public function getHadithLabels(array $ids): array
    {
        if (empty($ids)) {
            return [];
        }

        // Get column names from config
        $textColumn = config('content_sources.hadith.columns.text_ar', 'text_ar');
        $sourceColumn = config('content_sources.hadith.columns.collection', 'source');
        $hadithNoColumn = config('content_sources.hadith.columns.hadith_number', 'hadith_no');

        $results = DB::connection($this->connection())
            ->table($this->table())
            ->whereIn('id', $ids)
            ->select([
                'id',
                $sourceColumn . ' as source',
                $hadithNoColumn . ' as hadith_no',
                $textColumn . ' as text_ar',
            ])
            ->get();

        return $results->mapWithKeys(function ($row) {
            return [
                $row->id => $this->formatHadithLabel(
                    $row->source,
                    $row->hadith_no,
                    $row->text_ar
                ),
            ];
        })->toArray();
    }

    /**
     * Format a hadith label with match highlighting/snippet.
     * Shows the part of text containing the search term.
     */
    private function formatHadithLabelWithMatch(
        ?string $source,
        $hadithNo,
        string $text,
        string $searchTerm,
        string $normalizedTerm
    ): string {
        $prefix = $this->buildPrefix($source, $hadithNo);

        // Find the position of the search term in the text
        $pos = mb_stripos($text, $searchTerm);

        // If not found with original term, try normalized
        if ($pos === false && $normalizedTerm !== $searchTerm) {
            // Normalize the text too for comparison
            $normalizedText = ArabicTextNormalizer::normalize($text);
            $pos = mb_stripos($normalizedText, $normalizedTerm);
        }

        if ($pos !== false) {
            // Extract a snippet around the match
            $snippet = $this->extractSnippetAroundMatch($text, $pos, $searchTerm);
            return $prefix . $snippet;
        }

        // Fallback: just truncate from start
        return $prefix . $this->truncateText($text, self::MAX_LABEL_LENGTH);
    }

    /**
     * Extract a snippet of text around the matched position.
     */
    private function extractSnippetAroundMatch(string $text, int $matchPos, string $searchTerm): string
    {
        $textLength = mb_strlen($text);
        $termLength = mb_strlen($searchTerm);

        // Calculate start position (with context before the match)
        $start = max(0, $matchPos - self::CONTEXT_CHARS);

        // Calculate end position (match + context after)
        $end = min($textLength, $matchPos + $termLength + self::CONTEXT_CHARS);

        // Extract the snippet
        $snippet = mb_substr($text, $start, $end - $start);

        // Add ellipsis if we're not at the start/end
        $prefix = $start > 0 ? '…' : '';
        $suffix = $end < $textLength ? '…' : '';

        return $prefix . trim($snippet) . $suffix;
    }

    /**
     * Format a hadith label for display (without search context).
     * Used for getHadithLabels when showing selected items.
     */
    private function formatHadithLabel(?string $source, $hadithNo, string $text): string
    {
        $prefix = $this->buildPrefix($source, $hadithNo);
        return $prefix . $this->truncateText($text, self::MAX_LABEL_LENGTH);
    }

    /**
     * Build the prefix part of the label (source + number).
     */
    private function buildPrefix(?string $source, $hadithNo): string
    {
        if (!$source) {
            return '';
        }

        $prefix = trim($source);
        if ($hadithNo) {
            $prefix .= " #{$hadithNo}";
        }
        return $prefix . ': ';
    }

    /**
     * Truncate text to a maximum length with ellipsis.
     */
    private function truncateText(string $text, int $maxLength): string
    {
        if (mb_strlen($text) <= $maxLength) {
            return $text;
        }

        return mb_substr($text, 0, $maxLength) . '…';
    }
}
