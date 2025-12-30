<?php

namespace App\Filament\Resources;

use App\Domain\Quran\Models\QuranAyah;
use App\Filament\Resources\QuranAyahResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class QuranAyahResource extends Resource
{
    protected static ?string $model = QuranAyah::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationGroup = 'Religious Content';
    
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reference')
                    ->schema([
                        Forms\Components\Select::make('surah_id')
                            ->relationship('surah', 'name_en')
                            ->required()
                            ->searchable(),
                        Forms\Components\TextInput::make('number')
                            ->label('Ayah Number')
                            ->required()
                            ->numeric(),
                        Forms\Components\TextInput::make('page')
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Tabs::make('Content')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Arabic')
                            ->schema([
                                Forms\Components\Textarea::make('text')
                                    ->label('Arabic Text')
                                    ->required()
                                    ->rows(5)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('surah.name_en')
                    ->label('Surah')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('number')
                    ->label('Ayah #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('text')
                    ->label('Arabic Text')
                    ->limit(50)
                    ->extraAttributes(['dir' => 'rtl'])
                    ->searchable(),
            ])
            ->filters([
                 Tables\Filters\SelectFilter::make('surah_id')
                    ->label('Surah')
                    ->relationship('surah', 'name_en')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => config('content.admin_allow_delete', false)),
            ])
            ->bulkActions([
                 // Disable bulk delete by default for safety
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranAyahs::route('/'),
            'create' => Pages\CreateQuranAyah::route('/create'),
            'edit' => Pages\EditQuranAyah::route('/{record}/edit'),
        ];
    }
}
