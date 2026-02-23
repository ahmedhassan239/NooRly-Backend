<?php

namespace App\Filament\Resources;

use App\Domain\Duas\Dua;
use App\Filament\Concerns\HasScopeFilteredCategories;
use App\Filament\Resources\DuaResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DuaResource extends Resource
{
    use HasScopeFilteredCategories;

    
    protected static ?string $model = Dua::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Dua Content')
                    ->schema([
                        Forms\Components\TextInput::make('dua_key')
                            ->label('Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('category_key')
                            ->label('Category Key')
                            ->required()
                            ->maxLength(100),

                        Forms\Components\TextInput::make('source')
                            ->label('Source')
                            ->nullable(),
                    ])->columns(3),

                Forms\Components\Section::make('Categories')
                    ->schema([
                        static::getCategorySelectField('duas'),
                    ]),

                Forms\Components\Tabs::make('Translations')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('English')
                            ->schema([
                                Forms\Components\Textarea::make('text_en')
                                    ->label('English Text')
                                    ->nullable()
                                    ->rows(5)
                                    ->columnSpanFull(),
                                
                                Forms\Components\Textarea::make('transliteration')
                                    ->label('Transliteration')
                                    ->rows(2)
                                    ->columnSpanFull(),
                            ]),
                        
                        Forms\Components\Tabs\Tab::make('Arabic')
                            ->schema([
                                Forms\Components\Textarea::make('text_ar')
                                    ->label('Arabic Text')
                                    ->required()
                                    ->rows(5)
                                    ->columnSpanFull()
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('text_en')
                    ->label('Title (EN)')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('category_key')
                    ->label('Category')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => ucwords(str_replace('-', ' ', $state))),
                Tables\Columns\TextColumn::make('text_ar')
                    ->label('Arabic')
                    ->limit(50)
                    ->extraAttributes(['dir' => 'rtl']),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDuas::route('/'),
            'create' => Pages\CreateDua::route('/create'),
            'edit' => Pages\EditDua::route('/{record}/edit'),
        ];
    }
}
