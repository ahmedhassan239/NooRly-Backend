<?php

namespace App\Filament\Resources\DailyTaskResource\Pages;

use App\Filament\Resources\DailyTaskResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDailyTask extends EditRecord
{
    protected static string $resource = DailyTaskResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    
    protected function mutateFormDataBeforeFill(array $data): array
    {
        foreach ($this->record->translations as $translation) {
            $prefix = $translation->language_code . '_';
            foreach ($translation->getAttributes() as $key => $value) {
                if (!in_array($key, ['id', 'daily_task_id', 'language_code', 'created_at', 'updated_at'])) {
                    $data[$prefix . $key] = $value;
                }
            }
        }
        return $data;
    }
    
    protected function mutateFormDataBeforeSave(array $data): array
    {
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
            } else {
                $baseData[$key] = $value;
            }
        }
        
        $this->translationData = $translationData;
        return $baseData;
    }
    
    protected function afterSave(): void
    {
        if (!empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (!empty(array_filter($fields))) {
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
