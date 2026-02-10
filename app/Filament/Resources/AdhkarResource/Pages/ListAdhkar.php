<?php

namespace App\Filament\Resources\AdhkarResource\Pages;

use App\Filament\Resources\AdhkarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAdhkar extends ListRecords
{
    protected static string $resource = AdhkarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
