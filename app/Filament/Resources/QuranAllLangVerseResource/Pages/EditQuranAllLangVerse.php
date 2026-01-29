<?php

namespace App\Filament\Resources\QuranAllLangVerseResource\Pages;

use App\Filament\Resources\QuranAllLangVerseResource;
use Filament\Resources\Pages\EditRecord;

class EditQuranAllLangVerse extends EditRecord
{
    protected static string $resource = QuranAllLangVerseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Warning: Deleting this verse will also delete all its translations!'),
        ];
    }
}
