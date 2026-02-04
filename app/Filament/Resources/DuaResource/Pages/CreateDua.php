<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use App\Services\Categories\CategoryValidationService;
use Filament\Resources\Pages\CreateRecord;

class CreateDua extends CreateRecord
{
    protected static string $resource = DuaResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate categories before saving
        if (isset($data['categories']) && is_array($data['categories'])) {
            $validationService = app(CategoryValidationService::class);
            $validationService->validateCategoriesForScopeKey($data['categories'], 'duas');
        }

        // Store Quran Ayahs and Hadith Items for syncing
        $this->quranAyahIds = $data['quranAyahs'] ?? [];
        $this->hadithItemIds = $data['hadithItems'] ?? [];

        // Remove relationship fields from data
        unset($data['quranAyahs'], $data['hadithItems'], $data['categories']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Sync Quran Ayahs manually (cross-database relationship)
        if (!empty($this->quranAyahIds)) {
            $this->syncQuranAyahs($this->quranAyahIds);
        }

        // Sync Hadith Items manually (cross-database relationship)
        if (!empty($this->hadithItemIds)) {
            $this->syncHadithItems($this->hadithItemIds);
        }
    }

    private function syncQuranAyahs(array $ayahIds): void
    {
        $modelType = get_class($this->record);
        $modelId = $this->record->id;

        if (!empty($ayahIds)) {
            $insertData = array_map(function ($ayahId) use ($modelType, $modelId) {
                return [
                    'quran_ayah_id' => $ayahId,
                    'ayahable_type' => $modelType,
                    'ayahable_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $ayahIds);

            \Illuminate\Support\Facades\DB::table('quran_ayahables')->insert($insertData);
        }
    }

    private function syncHadithItems(array $hadithIds): void
    {
        $modelType = get_class($this->record);
        $modelId = $this->record->id;

        if (!empty($hadithIds)) {
            $insertData = array_map(function ($hadithId) use ($modelType, $modelId) {
                return [
                    'hadith_item_id' => $hadithId,
                    'hadithable_type' => $modelType,
                    'hadithable_id' => $modelId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }, $hadithIds);

            \Illuminate\Support\Facades\DB::table('hadith_itemables')->insert($insertData);
        }
    }

    private array $quranAyahIds = [];
    private array $hadithItemIds = [];
}
