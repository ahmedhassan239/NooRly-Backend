<?php

namespace App\Contracts;

/**
 * Interface for Hadith search service.
 * 
 * Defines the contract for searching Hadith items by Arabic text.
 */
interface HadithSearchServiceInterface
{
    /**
     * Search for Hadith items by Arabic text.
     * 
     * @param string $term The Arabic search term
     * @param int $limit Maximum number of results to return
     * @return array<int, string> Array of [hadith_id => label] pairs
     */
    public function searchArabicHadith(string $term, int $limit = 25): array;

    /**
     * Get labels for a list of hadith IDs.
     * 
     * @param array<int> $ids Array of hadith IDs
     * @return array<int, string> Array of [hadith_id => label] pairs
     */
    public function getHadithLabels(array $ids): array;
}
