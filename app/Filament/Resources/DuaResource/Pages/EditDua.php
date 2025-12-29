<?php

namespace App\Filament\Resources\DuaResource\Pages;

use App\Filament\Resources\DuaResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditDua extends EditRecord
{
    protected static string $resource = DuaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
    

}
