<?php

namespace App\Filament\Resources;

use App\Domain\QuranAllLang\Models\Language;
use App\Filament\Resources\QuranLanguageResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuranLanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static ?string $navigationGroup = 'Quran All Languages';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Languages';
    
    protected static ?string $modelLabel = 'Language';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Language Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->label('Language Code')
                            ->placeholder('en, ar, bn, zh')
                            ->helperText('ISO 639-1 or ISO 639-2 code'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->label('Language Name')
                            ->placeholder('English, Arabic, Bengali'),
                        Forms\Components\Toggle::make('is_rtl')
                            ->label('Right-to-Left (RTL)')
                            ->helperText('Enable for languages like Arabic, Hebrew, Urdu')
                            ->default(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Language $record): string => $record->code),
                Tables\Columns\IconColumn::make('is_rtl')
                    ->label('RTL')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('translations_count')
                    ->counts('translations')
                    ->label('Translations')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_rtl')
                    ->label('Direction')
                    ->placeholder('All directions')
                    ->trueLabel('RTL only')
                    ->falseLabel('LTR only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Warning: Deleting this language will also delete all its translations and verse texts!'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranLanguages::route('/'),
            'create' => Pages\CreateQuranLanguage::route('/create'),
            'edit' => Pages\EditQuranLanguage::route('/{record}/edit'),
        ];
    }
}
