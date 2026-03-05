<?php

namespace App\Application\DailyInspiration\Strategies;

use App\Domain\QuranAllLang\Helpers\SurahHelper;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\Verses\VerseCollection;

class VerseCollectionStrategy implements CollectionStrategyInterface
{
    public function type(): string
    {
        return 'ayah';
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
        return \Illuminate\Support\Facades\DB::table('verse_collections')
            ->whereExists(function ($q) {
                $q->select(\Illuminate\Support\Facades\DB::raw(1))
                    ->from('verse_collection_ayah')
                    ->whereColumn('verse_collection_ayah.verse_collection_id', 'verse_collections.id');
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
            throw new \RuntimeException('No verse collection with items.');
        }

        $randomKey = array_rand($collectionIds);
        $collectionId = $collectionIds[$randomKey];
        $collection = VerseCollection::with('translations')->find($collectionId);
        if (! $collection) {
            throw new \RuntimeException('Verse collection not found.');
        }

        $ayahIds = $collection->getQuranAyahIds();
        if ($ayahIds === []) {
            throw new \RuntimeException('Verse collection has no items.');
        }

        $pickedAyahId = $ayahIds[array_rand($ayahIds)];

        $verse = QuranVerse::with(['verseTexts' => fn ($q) => $q->forActiveLanguages()->with('translation.language')])
            ->find($pickedAyahId);

        if (! $verse) {
            throw new \RuntimeException('Quran verse not found in external DB.');
        }

        $texts = $verse->verseTexts->sortBy(fn ($vt) => match ($vt->translation->language->code ?? '') {
            'en' => 1, 'ar' => 2, default => 3,
        });
        $primaryText = $texts->first();
        $arabicText = $texts->firstWhere(fn ($vt) => ($vt->translation->language->code ?? '') === 'ar');

        $surahName = SurahHelper::getName($verse->surah_number);
        $source = "{$surahName} {$verse->ayah_number}";

        return [
            'type' => 'ayah',
            'id' => (int) $verse->id,
            'collection_id' => (int) $collection->id,
            'title' => $collection->getTitle($locale),
            'arabic' => (string) ($arabicText?->text ?? ''),
            'translation' => $primaryText?->text ?? null,
            'source' => $source,
        ];
    }
}
