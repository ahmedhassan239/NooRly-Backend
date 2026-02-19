<?php

namespace App\Filament\Resources;

use App\Domain\Journey\JourneyWeek;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\JourneyWeekResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

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
                        Forms\Components\Select::make('icon')
                            ->label('Icon (optional)')
                            ->placeholder('Select an icon')
                            ->options(fn () => collect(config('journey_icons', []))->mapWithKeys(fn (array $v, string $k) => [$k => ($v['emoji'] . ' ' . $v['label'])])->all())
                            ->searchable()
                            ->nullable()
                            ->helperText('Used in the mobile Journey UI')
                            ->rules([
                                'nullable',
                                Rule::in(Arr::wrap(array_keys(config('journey_icons', [])))),
                            ])
                            ->live(),
                        Forms\Components\Placeholder::make('icon_preview')
                            ->label('Preview')
                            ->content(fn (Get $get): string => static::iconPreviewContent($get('icon')))
                            ->visible(fn (Get $get): bool => filled($get('icon'))),
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
                Tables\Columns\TextColumn::make('icon')
                    ->label('Icon')
                    ->formatStateUsing(fn (?string $state): string => $state ? (static::iconPreviewContent($state)) : '—')
                    ->placeholder('—'),
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

    public static function iconPreviewContent(?string $iconKey): string
    {
        if (! $iconKey) {
            return '—';
        }
        $icons = config('journey_icons', []);
        $entry = $icons[$iconKey] ?? null;
        if (! $entry) {
            return '—';
        }
        return ($entry['emoji'] ?? '') . ' ' . ($entry['label'] ?? $iconKey);
    }
}
