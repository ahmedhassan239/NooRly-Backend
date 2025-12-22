<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListDuas extends ListRecords
{
    protected static string $resource = DuaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
