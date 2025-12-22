<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use Filament\Resources\Pages\CreateRecord;

class CreateDua extends CreateRecord
{
    protected static string $resource = DuaResource::class;
    
    protected function mutateFormDataBeforeCreate(array $data): array
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
    }
    
    private array $translationData = [];
}
