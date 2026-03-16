<?php

namespace App\Filament\Resources\HelpItemResource\Pages;

use App\Filament\Resources\HelpItemResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListHelpItems extends ListRecords
{
    protected static string $resource = HelpItemResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
