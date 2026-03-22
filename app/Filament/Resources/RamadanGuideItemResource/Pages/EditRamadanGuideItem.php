<?php

namespace App\Filament\Resources\RamadanGuideItemResource\Pages;

use App\Filament\Resources\RamadanGuideItemResource;
use App\Support\Ramadan\RamadanIconRegistry;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRamadanGuideItem extends EditRecord
{
    protected static string $resource = RamadanGuideItemResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $data['icon'] = RamadanIconRegistry::canonicalizeStoredKey($data['icon'] ?? '');

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
