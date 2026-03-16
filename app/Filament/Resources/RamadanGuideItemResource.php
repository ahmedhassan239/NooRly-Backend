<?php

namespace App\Filament\Resources;

use App\Domain\RamadanGuide\RamadanGuideItem;
use App\Filament\Resources\RamadanGuideItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class RamadanGuideItemResource extends Resource
{
    protected static ?string $model = RamadanGuideItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-moon';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 20;

    protected static ?string $navigationLabel = 'Ramadan Guide';

    protected static ?string $modelLabel = 'Ramadan Guide Item';

    protected static ?string $pluralModelLabel = 'Ramadan Guide Items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->schema([
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Sort Order')
                            ->numeric()
                            ->default(0),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon Key')
                            ->placeholder('moon, sun, warning, hands, sparkle, mosque, food, strength, star, money, celebration, refresh')
                            ->default('moon')
                            ->maxLength(50),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')->label('Title (EN)')->required()->maxLength(255),
                        Forms\Components\TextInput::make('description_en')->label('Description (EN)')->required()->maxLength(500),
                        Forms\Components\Textarea::make('content_en')->label('Content (EN)')->required()->rows(12)->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Arabic')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')->label('Title (AR)')->required()->maxLength(255)->extraAttributes(['dir' => 'rtl']),
                        Forms\Components\TextInput::make('description_ar')->label('Description (AR)')->required()->maxLength(500)->extraAttributes(['dir' => 'rtl']),
                        Forms\Components\Textarea::make('content_ar')->label('Content (AR)')->required()->rows(12)->columnSpanFull()->extraAttributes(['dir' => 'rtl']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title_en')->label('Title (EN)')->searchable()->limit(40),
                Tables\Columns\TextColumn::make('icon')->badge()->color('gray'),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')->label('Active'),
            ])
            ->actions([Tables\Actions\EditAction::make()])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRamadanGuideItems::route('/'),
            'create' => Pages\CreateRamadanGuideItem::route('/create'),
            'edit' => Pages\EditRamadanGuideItem::route('/{record}/edit'),
        ];
    }
}
