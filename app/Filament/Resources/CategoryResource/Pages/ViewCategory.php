<?php

namespace App\Filament\Resources\CategoryResource\Pages;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use App\Domain\Categories\Models\Category;
use App\Domain\Languages\Language;
use App\Filament\Resources\CategoryResource;
use Filament\Actions;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Pages\ViewRecord;

class ViewCategory extends ViewRecord
{
    protected static string $resource = CategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    public function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Translations Section
                Infolists\Components\Section::make('Translations')
                    ->description('Category name and description in all languages')
                    ->schema([
                        Infolists\Components\RepeatableEntry::make('translations')
                            ->label('')
                            ->schema([
                                Infolists\Components\Grid::make(3)
                                    ->schema([
                                        Infolists\Components\TextEntry::make('language_code')
                                            ->label('Language')
                                            ->badge()
                                            ->color('primary')
                                            ->formatStateUsing(fn (string $state): string => strtoupper($state)),
                                        Infolists\Components\TextEntry::make('name')
                                            ->label('Name')
                                            ->weight('bold')
                                            ->extraAttributes(fn ($record) => [
                                                'dir' => in_array($record->language_code, ['ar', 'fa', 'ur', 'he']) ? 'rtl' : 'ltr',
                                            ]),
                                        Infolists\Components\TextEntry::make('slug')
                                            ->label('Slug')
                                            ->color('gray')
                                            ->copyable(),
                                    ]),
                                Infolists\Components\TextEntry::make('description')
                                    ->label('Description')
                                    ->columnSpanFull()
                                    ->placeholder('No description')
                                    ->extraAttributes(fn ($record) => [
                                        'dir' => in_array($record->language_code, ['ar', 'fa', 'ur', 'he']) ? 'rtl' : 'ltr',
                                    ]),
                            ])
                            ->columns(1)
                            ->columnSpanFull(),
                    ]),

                // Missing Translations Alert
                Infolists\Components\Section::make('Translation Status')
                    ->schema([
                        Infolists\Components\TextEntry::make('missing_translations')
                            ->label('Missing Translations')
                            ->getStateUsing(function (Category $record): string {
                                $activeLanguages = Language::active()->pluck('code')->toArray();
                                $existingLanguages = $record->translations->pluck('language_code')->toArray();
                                $missing = array_diff($activeLanguages, $existingLanguages);
                                
                                if (empty($missing)) {
                                    return 'All translations complete';
                                }
                                
                                return 'Missing: ' . strtoupper(implode(', ', $missing));
                            })
                            ->badge()
                            ->color(function (Category $record): string {
                                $activeLanguages = Language::active()->pluck('code')->toArray();
                                $existingLanguages = $record->translations->pluck('language_code')->toArray();
                                $missing = array_diff($activeLanguages, $existingLanguages);
                                
                                return empty($missing) ? 'success' : 'warning';
                            }),
                    ])
                    ->collapsed(),

                // Quran Verses Section
                Infolists\Components\Section::make('Quran Verses (Ayat)')
                    ->description('Selected Quran verses in this category')
                    ->schema([
                        Infolists\Components\TextEntry::make('verses_count')
                            ->label('Total Selected')
                            ->getStateUsing(fn (Category $record): int => $record->verses_count)
                            ->badge()
                            ->color('success'),
                        Infolists\Components\RepeatableEntry::make('verses_details')
                            ->label('Verses')
                            ->getStateUsing(function (Category $record): array {
                                $service = app(QuranSearchServiceInterface::class);
                                $verseIds = $record->getVerseIds();
                                
                                if (empty($verseIds)) {
                                    return [];
                                }
                                
                                $labels = $service->getVerseLabels($verseIds);
                                
                                return collect($labels)->map(function ($label, $id) {
                                    return [
                                        'id' => $id,
                                        'label' => $label,
                                    ];
                                })->values()->toArray();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('label')
                                    ->label('')
                                    ->extraAttributes(['dir' => 'rtl'])
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Hadith Section
                Infolists\Components\Section::make('Hadith')
                    ->description('Selected Hadith items in this category')
                    ->schema([
                        Infolists\Components\TextEntry::make('hadiths_count')
                            ->label('Total Selected')
                            ->getStateUsing(fn (Category $record): int => $record->hadiths_count)
                            ->badge()
                            ->color('info'),
                        Infolists\Components\RepeatableEntry::make('hadiths_details')
                            ->label('Hadith')
                            ->getStateUsing(function (Category $record): array {
                                $service = app(HadithSearchServiceInterface::class);
                                $hadithIds = $record->getHadithIds();
                                
                                if (empty($hadithIds)) {
                                    return [];
                                }
                                
                                $labels = $service->getHadithLabels($hadithIds);
                                
                                return collect($labels)->map(function ($label, $id) {
                                    return [
                                        'id' => $id,
                                        'label' => $label,
                                    ];
                                })->values()->toArray();
                            })
                            ->schema([
                                Infolists\Components\TextEntry::make('label')
                                    ->label('')
                                    ->extraAttributes(['dir' => 'rtl'])
                                    ->columnSpanFull(),
                            ])
                            ->contained(false)
                            ->columnSpanFull(),
                    ])
                    ->collapsible(),

                // Metadata Section
                Infolists\Components\Section::make('Metadata')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('created_at')
                            ->label('Created At')
                            ->dateTime('F j, Y g:i A'),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->label('Updated At')
                            ->dateTime('F j, Y g:i A'),
                    ])
                    ->columns(3)
                    ->collapsed(),
            ]);
    }
}
