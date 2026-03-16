<?php

namespace App\Filament\Resources\RamadanGuideItemResource\Pages;

use App\Filament\Resources\RamadanGuideItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListRamadanGuideItems extends ListRecords
{
    protected static string $resource = RamadanGuideItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
