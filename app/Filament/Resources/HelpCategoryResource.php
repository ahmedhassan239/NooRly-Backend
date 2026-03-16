<?php

namespace App\Filament\Resources;

use App\Domain\HelpNow\HelpCategory;
use App\Filament\Resources\HelpCategoryResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HelpCategoryResource extends Resource
{
    protected static ?string $model = HelpCategory::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationLabel = 'Help Now (Categories)';

    protected static ?string $modelLabel = 'Help Category';

    protected static ?string $pluralModelLabel = 'Help Categories';

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
                        Forms\Components\TextInput::make('sort_order')->label('Sort Order')->numeric()->default(0),
                        Forms\Components\TextInput::make('icon')
                            ->label('Icon Key')
                            ->placeholder('question, mosque, family, heart, clipboard, support, user, people, refresh')
                            ->default('heart')
                            ->maxLength(50),
                        Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')->label('Title (EN)')->required()->maxLength(255),
                        Forms\Components\TextInput::make('description_en')->label('Description (EN)')->nullable()->maxLength(500),
                    ]),

                Forms\Components\Section::make('Arabic')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')->label('Title (AR)')->required()->maxLength(255)->extraAttributes(['dir' => 'rtl']),
                        Forms\Components\TextInput::make('description_ar')->label('Description (AR)')->nullable()->maxLength(500)->extraAttributes(['dir' => 'rtl']),
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
                Tables\Columns\TextColumn::make('items_count')->label('Items')->counts('items')->sortable(),
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
            'index' => Pages\ListHelpCategories::route('/'),
            'create' => Pages\CreateHelpCategory::route('/create'),
            'edit' => Pages\EditHelpCategory::route('/{record}/edit'),
        ];
    }
}
