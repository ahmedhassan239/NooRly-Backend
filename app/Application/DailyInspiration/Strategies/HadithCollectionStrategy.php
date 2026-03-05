<?php

namespace App\Application\DailyInspiration\Strategies;

use App\Domain\Hadith\HadithCollection;
use Illuminate\Support\Facades\DB;

class HadithCollectionStrategy implements CollectionStrategyInterface
{
    private array $hadithConfig;

    public function __construct()
    {
        $this->hadithConfig = [
            'connection' => config('content_sources.hadith.connection', 'mysql_hadith'),
            'table' => config('content_sources.hadith.table', 'all_hadiths_clean.hadiths'),
            'columns' => config('content_sources.hadith.columns', [
                'collection' => 'source',
                'book_number' => 'chapter_no',
                'hadith_number' => 'hadith_no',
                'text_ar' => 'text_ar',
                'text_en' => 'text_en',
            ]),
        ];
    }

    public function type(): string
    {
        return 'hadith';
    }

    public function isAvailable(): bool
    {
        return $this->getCollectionIdsWithItems() !== [];
    }

    public function getAvailableCollectionCount(): int
    {
        return count($this->getCollectionIdsWithItems());
    }

    /**
     * @return list<int>
     */
    private function getCollectionIdsWithItems(): array
    {
        return DB::table('library_hadith_collections')
            ->whereExists(function ($q) {
                $q->select(DB::raw(1))
                    ->from('lib_hadith_collection_item')
                    ->whereColumn('lib_hadith_collection_item.hadith_collection_id', 'library_hadith_collections.id');
            })
            ->orderBy('display_order')
            ->orderBy('id')
            ->pluck('id')
            ->toArray();
    }

    public function pick(string $locale): array
    {
        $collectionIds = $this->getCollectionIdsWithItems();
        if ($collectionIds === []) {
            throw new \RuntimeException('No hadith collection with items.');
        }

        $randomKey = array_rand($collectionIds);
        $collectionId = $collectionIds[$randomKey];
        $collection = HadithCollection::with('translations')->find($collectionId);
        if (! $collection) {
            throw new \RuntimeException('Hadith collection not found.');
        }

        $itemIds = $collection->getHadithItemIds();
        if ($itemIds === []) {
            throw new \RuntimeException('Hadith collection has no items.');
        }

        $pickedItemId = $itemIds[array_rand($itemIds)];

        $row = DB::connection($this->hadithConfig['connection'])
            ->table($this->hadithConfig['table'])
            ->where('id', $pickedItemId)
            ->first();

        if (! $row) {
            throw new \RuntimeException('Hadith item not found in external DB.');
        }

        $cols = $this->hadithConfig['columns'];
        $source = $this->formatSource($row->{$cols['collection']} ?? '');

        return [
            'type' => 'hadith',
            'id' => (int) $row->id,
            'collection_id' => (int) $collection->id,
            'title' => $collection->getTitle($locale),
            'arabic' => (string) ($row->{$cols['text_ar']} ?? ''),
            'translation' => $row->{$cols['text_en']} ?? null,
            'source' => $source,
        ];
    }

    private function formatSource(string $source): string
    {
        $names = [
            'bukhari' => 'Sahih al-Bukhari',
            'muslim' => 'Sahih Muslim',
            'tirmidhi' => 'Jami` at-Tirmidhi',
            'abudawud' => 'Sunan Abu Dawud',
            'nasai' => 'Sunan an-Nasa\'i',
            'ibnmajah' => 'Sunan Ibn Majah',
            'malik' => 'Muwatta Malik',
            'ahmad' => 'Musnad Ahmad',
        ];

        return $names[strtolower($source)] ?? ucfirst($source);
    }
}
