<?php

namespace App\Filament\Resources\QuranTranslationResource\Pages;

use App\Filament\Resources\QuranTranslationResource;
use Filament\Resources\Pages\EditRecord;

class EditQuranTranslation extends EditRecord
{
    protected static string $resource = QuranTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\ViewAction::make(),
            \Filament\Actions\DeleteAction::make()
                ->requiresConfirmation()
                ->modalDescription('Warning: Deleting this translation will also delete all its verse texts!'),
        ];
    }
}
