<?php

namespace App\Filament\Resources\QuranTranslationResource\Pages;

use App\Filament\Resources\QuranTranslationResource;
use Filament\Resources\Pages\ListRecords;

class ListQuranTranslations extends ListRecords
{
    protected static string $resource = QuranTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
