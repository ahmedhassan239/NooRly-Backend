<?php

namespace App\Filament\Resources\QuranLanguageResource\Pages;

use App\Domain\QuranAllLang\Models\Language;
use App\Filament\Resources\QuranLanguageResource;
use Filament\Resources\Pages\ListRecords;

class ListQuranLanguages extends ListRecords
{
    protected static string $resource = QuranLanguageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('resetDefaults')
                ->label('Reset Defaults')
                ->icon('heroicon-o-arrow-path')
                ->color('warning')
                ->requiresConfirmation()
                ->modalHeading('Reset Language Defaults')
                ->modalDescription('This will set all languages to inactive, then activate only Arabic (ar) and English (en). This action cannot be undone.')
                ->action(function (): void {
                    // Set all languages to inactive
                    Language::query()->update(['is_active' => false]);
                    
                    // Set only ar and en to active
                    Language::whereIn('code', ['ar', 'en'])->update(['is_active' => true]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Defaults Reset')
                        ->body('All languages have been set to inactive. Arabic and English are now active.')
                        ->success()
                        ->send();
                    
                    // Refresh the table
                    $this->dispatch('refresh-table');
                }),
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
