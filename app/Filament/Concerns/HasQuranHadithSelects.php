<?php

namespace App\Filament\Concerns;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use Filament\Forms\Components\Select;

/**
 * Trait HasQuranHadithSelects
 *
 * Provides methods to create Quran Ayahs and Hadith Items select fields.
 * Quran Ayah labels are "Surah Name • Ayah Number" (locale-aware via QuranSearchService).
 */
trait HasQuranHadithSelects
{
    /**
     * Get Quran Ayahs (verses) multi-select field.
     * Labels show "Surah Name • AyahNo" (EN or AR per app locale). Search works by Arabic text and surah name.
     *
     * @return Select
     */
    protected static function getQuranAyahsSelectField(): Select
    {
        $quranService = app(QuranSearchServiceInterface::class);

        return Select::make('quranAyahs')
            ->label('Quran Ayahs (Ayat)')
            ->options(function ($record) use ($quranService) {
                // Load existing verse IDs from pivot table (main DB)
                if ($record && $record->exists) {
                    $verseIds = \Illuminate\Support\Facades\DB::table('quran_ayahables')
                        ->where('ayahable_type', get_class($record))
                        ->where('ayahable_id', $record->id)
                        ->pluck('quran_ayah_id')
                        ->toArray();

                    if (!empty($verseIds)) {
                        return $quranService->getVerseLabels($verseIds);
                    }
                }
                return [];
            })
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($quranService) {
                // Use the search service for Arabic text search
                if (mb_strlen(trim($search)) < 2) {
                    return [];
                }
                return $quranService->searchArabicVerses($search, 50);
            })
            ->getOptionLabelUsing(function ($value) use ($quranService) {
                if (!$value) {
                    return '';
                }
                // Get label for selected value
                $labels = $quranService->getVerseLabels([$value]);
                return $labels[$value] ?? "Verse #{$value}";
            })
            ->multiple()
            ->placeholder('Search and select Quran verses...')
            ->helperText('Search by Arabic text to find and attach Quran verses.')
            ->dehydrated(true)
            ->columnSpanFull();
    }

    /**
     * Get Hadith Items multi-select field.
     *
     * @return Select
     */
    protected static function getHadithItemsSelectField(): Select
    {
        $hadithService = app(HadithSearchServiceInterface::class);

        return Select::make('hadithItems')
            ->label('Hadith Items')
            ->options(function ($record) use ($hadithService) {
                // Load existing hadith IDs from pivot table (main DB)
                if ($record && $record->exists) {
                    $hadithIds = \Illuminate\Support\Facades\DB::table('hadith_itemables')
                        ->where('hadithable_type', get_class($record))
                        ->where('hadithable_id', $record->id)
                        ->pluck('hadith_item_id')
                        ->toArray();

                    if (!empty($hadithIds)) {
                        return $hadithService->getHadithLabels($hadithIds);
                    }
                }
                return [];
            })
            ->searchable()
            ->getSearchResultsUsing(function (string $search) use ($hadithService) {
                // Use the search service for Arabic text search
                if (mb_strlen(trim($search)) < 2) {
                    return [];
                }
                return $hadithService->searchArabicHadith($search, 50);
            })
            ->getOptionLabelUsing(function ($value) use ($hadithService) {
                if (!$value) {
                    return '';
                }
                // Get label for selected value
                $labels = $hadithService->getHadithLabels([$value]);
                return $labels[$value] ?? "Hadith #{$value}";
            })
            ->multiple()
            ->placeholder('Search and select Hadith items...')
            ->helperText('Search by Arabic text to find and attach Hadith items.')
            ->dehydrated(true)
            ->columnSpanFull();
    }
}
