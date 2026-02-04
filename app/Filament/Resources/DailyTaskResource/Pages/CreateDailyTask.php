<?php

namespace App\Filament\Resources\DailyTaskResource\Pages;

use App\Filament\Resources\DailyTaskResource;
use App\Services\Categories\CategoryValidationService;
use Filament\Resources\Pages\CreateRecord;

class CreateDailyTask extends CreateRecord
{
    protected static string $resource = DailyTaskResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        // Validate categories before saving
        if (isset($data['categories']) && is_array($data['categories'])) {
            $validationService = app(CategoryValidationService::class);
            $validationService->validateCategoriesForScopeKey($data['categories'], 'daily_tasks');
        }

        // Store Quran Ayahs and Hadith Items for syncing
        $this->quranAyahIds = $data['quranAyahs'] ?? [];
        $this->hadithItemIds = $data['hadithItems'] ?? [];

        $translationData = [];
        $baseData = [];
        
        foreach ($data as $key => $value) {
            if (preg_match('/^(en|ar)_(.+)$/', $key, $matches)) {
                $langCode = $matches[1];
                $field = $matches[2];
                
                if (!isset($translationData[$langCode])) {
                    $translationData[$langCode] = [];
                }
                $translationData[$langCode][$field] = $value;
            } elseif (!in_array($key, ['quranAyahs', 'hadithItems', 'categories'])) {
                // Exclude relationship fields from base data
                $baseData[$key] = $value;
            }
        }
        
        $this->translationData = $translationData;
        return $baseData;
    }
    
    protected function afterCreate(): void
    {
        if (!empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (!empty(array_filter($fields))) {
                    $this->record->translations()->create([
                        'language_code' => $langCode,
                        ...$fields,
                    ]);
                }
            }
        }

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

    private array $translationData = [];
    private array $quranAyahIds = [];
    private array $hadithItemIds = [];
}
