<?php

namespace App\Filament\Resources\LessonResource\Pages;

use App\Filament\Resources\LessonResource;
use Filament\Resources\Pages\CreateRecord;

class CreateLesson extends CreateRecord
{
    protected static string $resource = LessonResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
    {
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
            } else {
                $baseData[$key] = $value;
            }
        }
        
        // Store translations for after create
        $this->translationData = $translationData;
        
        return $baseData;
    }
    
    protected function afterCreate(): void
    {
        // Save translations
        if (!empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (!empty(array_filter($fields))) { // Only save if there's actual data
                    $this->record->translations()->create([
                        'language_code' => $langCode,
                        ...$fields,
                    ]);
                }
            }
        }
    }
    
    private array $translationData = [];
}
