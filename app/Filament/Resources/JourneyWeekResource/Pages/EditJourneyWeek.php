<?php

namespace App\Filament\Resources\JourneyWeekResource\Pages;

use App\Filament\Resources\JourneyWeekResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditJourneyWeek extends EditRecord
{
    protected static string $resource = JourneyWeekResource::class;

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
            foreach (['title', 'description'] as $field) {
                if (isset($translation->{$field})) {
                    $data[$prefix . $field] = $translation->{$field};
                }
            }
        }
        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->translationData = [];
        $baseData = [];
        foreach ($data as $key => $value) {
            if (preg_match('/^(en|ar)_(.+)$/', $key, $matches)) {
                $langCode = $matches[1];
                $field = $matches[2];
                if (! isset($this->translationData[$langCode])) {
                    $this->translationData[$langCode] = [];
                }
                $this->translationData[$langCode][$field] = $value;
            } else {
                $baseData[$key] = $value;
            }
        }
        return $baseData;
    }

    protected function afterSave(): void
    {
        if (! empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (! empty(array_filter($fields))) {
                    $this->record->translations()->updateOrCreate(
                        ['language_code' => $langCode],
                        $fields
                    );
                }
            }
        }
    }

    /** @var array<string, array<string, mixed>> */
    protected array $translationData = [];
}
