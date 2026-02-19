<?php

namespace App\Filament\Resources\JourneyWeekResource\Pages;

use App\Filament\Resources\JourneyWeekResource;
use Filament\Resources\Pages\CreateRecord;

class CreateJourneyWeek extends CreateRecord
{
    protected static string $resource = JourneyWeekResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
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
        // journey_weeks.title is required; use first available translation title
        if (empty($baseData['title'])) {
            $baseData['title'] = $data['en_title'] ?? $data['ar_title'] ?? 'Week ' . ($baseData['week_number'] ?? '');
        }
        if (! array_key_exists('description', $baseData)) {
            $baseData['description'] = $data['en_description'] ?? $data['ar_description'] ?? null;
        }
        return $baseData;
    }

    protected function afterCreate(): void
    {
        if (! empty($this->translationData)) {
            foreach ($this->translationData as $langCode => $fields) {
                if (! empty(array_filter($fields))) {
                    $this->record->translations()->create([
                        'language_code' => $langCode,
                        ...$fields,
                    ]);
                }
            }
        }
    }

    /** @var array<string, array<string, mixed>> */
    protected array $translationData = [];
}
