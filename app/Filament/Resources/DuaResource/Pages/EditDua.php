<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use App\Services\Categories\CategoryValidationService;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDua extends EditRecord
{
    protected static string $resource = DuaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load existing Quran Ayahs and Hadith Items from pivot tables
        $data['quranAyahs'] = \Illuminate\Support\Facades\DB::table('quran_ayahables')
            ->where('ayahable_type', get_class($this->record))
            ->where('ayahable_id', $this->record->id)
            ->pluck('quran_ayah_id')
            ->toArray();
        
        $data['hadithItems'] = \Illuminate\Support\Facades\DB::table('hadith_itemables')
            ->where('hadithable_type', get_class($this->record))
            ->where('hadithable_id', $this->record->id)
            ->pluck('hadith_item_id')
            ->toArray();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
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

    protected function afterSave(): void
    {
        // Sync Quran Ayahs manually (cross-database relationship)
        $this->syncQuranAyahs($this->quranAyahIds ?? []);

        // Sync Hadith Items manually (cross-database relationship)
        $this->syncHadithItems($this->hadithItemIds ?? []);
    }

    private function syncQuranAyahs(array $ayahIds): void
    {
        $modelType = get_class($this->record);
        $modelId = $this->record->id;

        // Delete existing relationships
        \Illuminate\Support\Facades\DB::table('quran_ayahables')
            ->where('ayahable_type', $modelType)
            ->where('ayahable_id', $modelId)
            ->delete();

        // Insert new relationships
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

        // Delete existing relationships
        \Illuminate\Support\Facades\DB::table('hadith_itemables')
            ->where('hadithable_type', $modelType)
            ->where('hadithable_id', $modelId)
            ->delete();

        // Insert new relationships
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
