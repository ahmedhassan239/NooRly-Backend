<?php

namespace App\Filament\Resources\LessonResource\Pages;

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
        
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
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
                    $this->record->translations()->updateOrCreate(
                        ['language_code' => $langCode],
                        $fields
                    );
                }
            }
        }
    }
    
    private array $translationData = [];
}
