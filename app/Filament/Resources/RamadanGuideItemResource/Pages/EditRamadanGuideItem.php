<?php

namespace App\Filament\Resources\RamadanGuideItemResource\Pages;

use App\Filament\Resources\RamadanGuideItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditRamadanGuideItem extends EditRecord
{
    protected static string $resource = RamadanGuideItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
