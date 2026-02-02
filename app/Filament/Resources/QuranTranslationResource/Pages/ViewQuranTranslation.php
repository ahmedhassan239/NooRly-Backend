<?php

namespace App\Filament\Resources\QuranTranslationResource\Pages;

use App\Domain\QuranAllLang\Models\Translation;
use App\Filament\Resources\QuranTranslationResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranTranslation extends ViewRecord
{
    protected static string $resource = QuranTranslationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\EditAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Translation Details')
                    ->schema([
                        Infolists\Components\TextEntry::make('language.name')
                            ->label('Language')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('language.code')
                            ->label('Language Code')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\IconEntry::make('language.is_rtl')
                            ->label('RTL')
                            ->boolean(),
                        Infolists\Components\TextEntry::make('source_name')
                            ->label('Translator / Edition'),
                        Infolists\Components\TextEntry::make('file_name')
                            ->label('Source File'),
                        Infolists\Components\TextEntry::make('verse_texts_count')
                            ->label('Total Verses')
                            ->state(fn (Translation $record): string => 
                                number_format($record->verseTexts()->count())
                            )
                            ->badge()
                            ->color('primary')
                            ->visible(fn (Translation $record): bool => $record->language->is_active),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('Sample Verses')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('sampleVerseTexts')
                            ->label('')
                            ->getStateUsing(fn (Translation $record) => 
                                $record->language->is_active 
                                    ? $record->verseTexts()->forActiveLanguages()->limit(5)->get()
                                    : collect()
                            )
                            ->schema([
                                Infolists\Components\TextEntry::make('verse.ayah_key')
                                    ->label('Reference')
                                    ->badge(),
                                Infolists\Components\TextEntry::make('text')
                                    ->label('Text')
                                    ->limit(200)
                                    ->extraAttributes(fn ($record): array => [
                                        'dir' => $record->translation->language->is_rtl ? 'rtl' : 'ltr',
                                    ]),
                            ])
                            ->columns(2)
                            ->visible(fn (Translation $record): bool => $record->language->is_active),
                    ])
                    ->collapsible()
                    ->collapsed(false),
            ]);
    }
}
