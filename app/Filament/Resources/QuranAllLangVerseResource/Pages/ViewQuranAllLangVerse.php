<?php

namespace App\Filament\Resources\QuranAllLangVerseResource\Pages;

use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Filament\Resources\QuranAllLangVerseResource;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewQuranAllLangVerse extends ViewRecord
{
    protected static string $resource = QuranAllLangVerseResource::class;

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
                Infolists\Components\Section::make('Verse Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('surah_number')
                            ->label('Surah Number')
                            ->badge()
                            ->color('primary'),
                        Infolists\Components\TextEntry::make('ayah_number')
                            ->label('Ayah Number')
                            ->badge()
                            ->color('success'),
                        Infolists\Components\TextEntry::make('ayah_key')
                            ->label('Reference')
                            ->badge()
                            ->color('warning'),
                        Infolists\Components\TextEntry::make('full_reference')
                            ->label('Full Reference')
                            ->state(fn (QuranVerse $record): string => $record->full_reference),
                        Infolists\Components\TextEntry::make('verse_texts_count')
                            ->label('Total Translations (Active)')
                            ->state(fn (QuranVerse $record): string => 
                                number_format($record->verseTexts()->forActiveLanguages()->count())
                            )
                            ->badge()
                            ->color('info'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                    ])->columns(3),
                    
                Infolists\Components\Section::make('Translations')
                    ->description('All translations for this verse (active languages only)')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('verseTexts')
                            ->getStateUsing(function (QuranVerse $record) {
                                // Use the single source of truth method
                                $verseTexts = $record->verseTexts()
                                    ->orderByLanguagePriority()
                                    ->get();
                                
                                // Manually load relationships for each verse text
                                // (Cannot use ->with() after JOINs as it conflicts)
                                foreach ($verseTexts as $verseText) {
                                    if (!$verseText->relationLoaded('translation')) {
                                        $verseText->load('translation.language');
                                    }
                                }
                                
                                return $verseTexts;
                            })
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('translation.language.name')
                                            ->label('Language')
                                            ->badge()
                                            ->color(fn ($state, $record): string => 
                                                $record->translation->language->is_rtl ? 'warning' : 'success'
                                            ),
                                        Infolists\Components\TextEntry::make('translation.source_name')
                                            ->label('Translator / Edition'),
                                        Infolists\Components\IconEntry::make('translation.language.is_rtl')
                                            ->label('RTL')
                                            ->boolean(),
                                    ]),
                                Infolists\Components\TextEntry::make('text')
                                    ->label('Text')
                                    ->columnSpanFull()
                                    ->prose()
                                    ->extraAttributes(fn ($record): array => [
                                        'dir' => $record->translation->language->is_rtl ? 'rtl' : 'ltr',
                                        'class' => 'text-lg leading-relaxed',
                                    ]),
                                Infolists\Components\TextEntry::make('updated_at')
                                    ->label('Last Updated')
                                    ->dateTime()
                                    ->size('sm')
                                    ->color('gray'),
                            ])
                            ->columns(1)
                            ->contained(true),
                    ]),
            ]);
    }
}
