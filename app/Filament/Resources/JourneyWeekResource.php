<?php

namespace App\Filament\Resources;

use App\Domain\Journey\JourneyWeek;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\JourneyWeekResource\Pages;
use App\Filament\Support\PublicIconSelect;
use App\Support\Icons\PublicIconsRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class JourneyWeekResource extends Resource
{
    use HasTranslatableTabs;

    protected static ?string $model = JourneyWeek::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Content';

    protected static ?string $modelLabel = 'Journey Week';

    protected static ?string $pluralModelLabel = 'Journey Weeks';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Week details')
                    ->schema([
                        Forms\Components\TextInput::make('week_number')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->unique(ignoreRecord: true)
                            ->label('Week number'),
                        PublicIconSelect::make('icon', 'Icon (optional)', false)
                            ->helperText('Used in the mobile Journey UI')
                            ->columnSpanFull(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true)
                            ->label('Active'),
                    ])
                    ->columns(2),
                static::getTranslationTabs(function ($langCode, $isRequired) {
                    return [
                        Forms\Components\TextInput::make("{$langCode}_title")
                            ->label('Title')
                            ->required($isRequired)
                            ->maxLength(255),
                        Forms\Components\Textarea::make("{$langCode}_description")
                            ->label('Description')
                            ->maxLength(65535)
                            ->columnSpanFull()
                            ->rows(3),
                    ];
                }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('week_number')
                    ->numeric()
                    ->sortable()
                    ->label('Week'),
                Tables\Columns\ImageColumn::make('icon_thumb')
                    ->label('Icon')
                    ->getStateUsing(fn (JourneyWeek $record): ?string => PublicIconsRegistry::expand($record->icon)['icon_url'])
                    ->checkFileExistence(false)
                    ->height(28),
                Tables\Columns\TextColumn::make('title')
                    ->label('Title')
                    ->getStateUsing(fn (JourneyWeek $record) => $record->translations()->where('language_code', 'en')->first()?->title ?? $record->getTitleForLocale('en')),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active'),
                Tables\Columns\TextColumn::make('journeyWeekLessons_count')
                    ->counts('journeyWeekLessons')
                    ->label('Lessons'),
            ])
            ->defaultSort('week_number')
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListJourneyWeeks::route('/'),
            'create' => Pages\CreateJourneyWeek::route('/create'),
            'edit' => Pages\EditJourneyWeek::route('/{record}/edit'),
        ];
    }

    public static function getRelations(): array
    {
        return [
            JourneyWeekResource\RelationManagers\WeekLessonsRelationManager::class,
        ];
    }

}
