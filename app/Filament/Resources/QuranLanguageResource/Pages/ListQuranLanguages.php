<?php

namespace App\Filament\Resources\QuranLanguageResource\Pages;

use App\Filament\Resources\QuranLanguageResource;
use Filament\Resources\Pages\ListRecords;

class ListQuranLanguages extends ListRecords
{
    protected static string $resource = QuranLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
