<?php

namespace App\Filament\Resources;

use App\Domain\Hadith\HadithCollection;
use App\Filament\Resources\HadithCollectionResource\Pages;
use App\Filament\Support\PublicIconSelect;
use App\Support\Icons\PublicIconsRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HadithCollectionResource extends Resource
{
    protected static ?string $model = HadithCollection::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    protected static ?string $navigationGroup = 'Content';
    protected static ?string $navigationLabel = 'Hadith Collections';
    protected static ?string $modelLabel = 'Hadith Collection';
    protected static ?string $pluralModelLabel = 'Hadith Collections';

    public static function form(Form $form): Form
    {
        $hadithService = app(\App\Contracts\HadithSearchServiceInterface::class);

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
                Forms\Components\Section::make('Hadith Items')
                    ->schema([
                        Forms\Components\Select::make('hadithItemIds')
                            ->label('Hadith Items')
                            ->options(function ($record) use ($hadithService) {
                                if (!$record || !$record->exists) {
                                    return [];
                                }
                                $ids = $record->getHadithItemIds();
                                return $hadithService->getHadithLabels($ids);
                            })
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search) use ($hadithService) {
                                if (mb_strlen(trim($search)) < 2) {
                                    return [];
                                }
                                return $hadithService->searchArabicHadith($search, 50);
                            })
                            ->getOptionLabelUsing(function ($value) use ($hadithService) {
                                if (!$value) {
                                    return '';
                                }
                                $labels = $hadithService->getHadithLabels([$value]);
                                return $labels[$value] ?? "Hadith #{$value}";
                            })
                            ->multiple()
                            ->placeholder('Search and select hadith items...')
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
                    ->getStateUsing(fn (HadithCollection $record): ?string => PublicIconsRegistry::expand($record->icon)['icon_url'])
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
            'index' => Pages\ListHadithCollections::route('/'),
            'create' => Pages\CreateHadithCollection::route('/create'),
            'edit' => Pages\EditHadithCollection::route('/{record}/edit'),
        ];
    }

}
