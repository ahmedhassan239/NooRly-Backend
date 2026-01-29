<?php

namespace App\Filament\Resources\QuranAllLangVerseResource\Pages;

use App\Filament\Resources\QuranAllLangVerseResource;
use Filament\Resources\Pages\ListRecords;

class ListQuranAllLangVerses extends ListRecords
{
    protected static string $resource = QuranAllLangVerseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
