<?php

namespace App\Contracts;

/**
 * Interface for Quran verse search service.
 * 
 * Defines the contract for searching Quran verses by Arabic text.
 */
interface QuranSearchServiceInterface
{
    /**
     * Search for Quran verses by Arabic text.
     * 
     * @param string $term The Arabic search term
     * @param int $limit Maximum number of results to return
     * @return array<int, string> Array of [verse_id => label] pairs
     */
    public function searchArabicVerses(string $term, int $limit = 25): array;

    /**
     * Get labels for a list of verse IDs.
     * 
     * @param array<int> $ids Array of verse IDs
     * @return array<int, string> Array of [verse_id => label] pairs
     */
    public function getVerseLabels(array $ids): array;
}
