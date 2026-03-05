<?php

namespace App\Application\DailyInspiration\Strategies;

/**
 * Strategy to pick one random item from library collections (hadith or verse).
 * Item must come from collection pivot IDs only; details fetched from external DB.
 */
interface CollectionStrategyInterface
{
    /** Type key returned in payload: "hadith" or "ayah". */
    public function type(): string;

    /** Whether at least one collection has at least one item. */
    public function isAvailable(): bool;

    /** Number of collections that have at least one item (for debug). */
    public function getAvailableCollectionCount(): int;

    /**
     * Pick one random collection with items, then one random item from its pivot, fetch from external DB.
     *
     * @return array{type: string, id: int, collection_id: int, title: string, arabic: string, translation: string|null, source: string|null}
     */
    public function pick(string $locale): array;
}
