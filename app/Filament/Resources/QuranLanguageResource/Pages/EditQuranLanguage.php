<?php

namespace App\Filament\Resources\QuranLanguageResource\Pages;

use App\Filament\Resources\QuranLanguageResource;
use Filament\Resources\Pages\EditRecord;

class EditQuranLanguage extends EditRecord
{
    protected static string $resource = QuranLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Warning: Deleting this language will also delete all its translations and verse texts!'),
        ];
    }
}
