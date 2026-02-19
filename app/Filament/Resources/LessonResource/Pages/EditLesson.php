<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Domain\Lessons\Lesson;
use App\Filament\Resources\LessonResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLesson extends EditRecord
{
    protected static string $resource = LessonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        // Load translations into form fields
        foreach ($this->record->translations as $translation) {
            $prefix = $translation->language_code . '_';
            
            foreach ($translation->getAttributes() as $key => $value) {
                if (!in_array($key, ['id', 'lesson_id', 'language_code', 'created_at', 'updated_at'])) {
                    $data[$prefix . $key] = $value;
                }
            }
        }

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
        // Store Quran Ayahs and Hadith Items for syncing
        $this->quranAyahIds = $data['quranAyahs'] ?? [];
        $this->hadithItemIds = $data['hadithItems'] ?? [];

        // Extract translation data before saving
        $translationData = [];
        $baseData = [];
        
        foreach ($data as $key => $value) {
            if (preg_match('/^(en|ar)_(.+)$/', $key, $matches)) {
                $langCode = $matches[1];
                $field = $matches[2];
                
                if (!isset($translationData[$langCode])) {
                    $translationData[$langCode] = [];
                }
                
                // Skip customize_slug toggle
                if ($field !== 'customize_slug' && $field !== 'slug_disabled') {
                    $translationData[$langCode][$field] = $value;
                }
            } elseif (!in_array($key, ['quranAyahs', 'hadithItems'])) {
                // Exclude relationship fields from base data
                $baseData[$key] = $value;
            }
        }
        
        // Store translations for after save
        $this->translationData = $translationData;
        
        return $baseData;
    }
    
    protected function afterSave(): void
    {
        // Update translations
        if (!empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (!empty(array_filter($fields))) { // Only save if there's actual data
                    $normalized = $fields;
                    if (! array_key_exists('content', $normalized) || $normalized['content'] === null) {
                        $normalized['content'] = '';
                    }
                    $this->record->translations()->updateOrCreate(
                        ['language_code' => $langCode],
                        $normalized
                    );
                }
            }
        }

        // Sync Quran Ayahs manually (cross-database relationship)
        $this->syncQuranAyahs($this->quranAyahIds ?? []);

        // Sync Hadith Items manually (cross-database relationship)
        $this->syncHadithItems($this->hadithItemIds ?? []);

        // Remove any lesson–category pivot rows (lessons no longer use categories)
        \Illuminate\Support\Facades\DB::table('categorizables')
            ->where('categorizable_type', get_class($this->record))
            ->where('categorizable_id', $this->record->id)
            ->delete();
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
    
    private array $translationData = [];
    private array $quranAyahIds = [];
    private array $hadithItemIds = [];
}
