<?php

namespace App\Filament\Resources;

use App\Domain\Verses\VerseCollection;
use App\Filament\Resources\VerseCollectionResource\Pages;
use App\Filament\Support\PublicIconSelect;
use App\Support\Icons\PublicIconsRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class VerseCollectionResource extends Resource
{
    protected static ?string $model = VerseCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-bookmark';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Verse Collections';
    protected static ?string $modelLabel = 'Verse Collection';
    protected static ?string $pluralModelLabel = 'Verse Collections';

    public static function form(Form $form): Form
    {
        $quranService = app(\App\Contracts\QuranSearchServiceInterface::class);

        return $form
            ->schema([
                Forms\Components\Section::make('Collection')
                    ->schema([
                        Forms\Components\TextInput::make('display_order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0),
                        PublicIconSelect::make('icon', 'Icon (optional)', false)
                            ->helperText('Shown on collection cards in the mobile app')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Forms\Components\Tabs::make('Translations')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('English')
                            ->schema([
                                Forms\Components\TextInput::make('en_title')
                                    ->label('Title')
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('en_slug', Str::slug($state ?? ''))),
                                Forms\Components\TextInput::make('en_slug')
                                    ->label('Slug')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('en_description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ]),
                        Forms\Components\Tabs\Tab::make('Arabic')
                            ->schema([
                                Forms\Components\TextInput::make('ar_title')
                                    ->label('Title')
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn ($state, callable $set) => $set('ar_slug', Str::slug($state ?? ''))),
                                Forms\Components\TextInput::make('ar_slug')
                                    ->label('Slug')
                                    ->maxLength(255),
                                Forms\Components\Textarea::make('ar_description')
                                    ->label('Description')
                                    ->rows(3)
                                    ->nullable()
                                    ->columnSpanFull(),
                            ])
                            ->extraAttributes(['dir' => 'rtl']),
                    ])
                    ->columnSpanFull()
                    ->persistTabInQueryString(),
                Forms\Components\Section::make('Quran Verses')
                    ->schema([
                        Forms\Components\Select::make('quranAyahIds')
                            ->label('Quran Verses (Ayat)')
                            ->options(function ($record) use ($quranService) {
                                if (!$record || !$record->exists) {
                                    return [];
                                }
                                $ids = $record->getQuranAyahIds();
                                return $quranService->getVerseLabels($ids);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) use ($quranService) {
                                if (mb_strlen(trim($search)) < 2) {
                                    return [];
                                }
                                return $quranService->searchArabicVerses($search, 50);
                            })
                            ->getOptionLabelUsing(function ($value) use ($quranService) {
                                if (!$value) {
                                    return '';
                                }
                                $labels = $quranService->getVerseLabels([$value]);
                                return $labels[$value] ?? "Verse #{$value}";
                            })
                            ->multiple()
                            ->placeholder('Search and select Quran verses...')
                            ->helperText('Order in list = display order.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_order')->sortable()->label('#'),
                Tables\Columns\ImageColumn::make('icon_thumb')
                    ->label('Icon')
                    ->getStateUsing(fn (VerseCollection $record): ?string => PublicIconsRegistry::expand($record->icon)['icon_url'])
                    ->checkFileExistence(false)
                    ->height(28),
                Tables\Columns\TextColumn::make('title')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('slug')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('display_order')
            ->reorderable('display_order')
            ->filters([])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVerseCollections::route('/'),
            'create' => Pages\CreateVerseCollection::route('/create'),
            'edit' => Pages\EditVerseCollection::route('/{record}/edit'),
        ];
    }

}
