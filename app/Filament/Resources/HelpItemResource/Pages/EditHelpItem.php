<?php

namespace App\Filament\Resources\HelpItemResource\Pages;

use App\Filament\Resources\HelpItemResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditHelpItem extends EditRecord
{
    protected static string $resource = HelpItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
