<?php

namespace App\Filament\Resources;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\Translation;
use App\Filament\Resources\QuranTranslationResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuranTranslationResource extends Resource
{
    protected static ?string $model = Translation::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationGroup = 'Quran All Languages';
    
    protected static ?int $navigationSort = 2;
    
    protected static ?string $navigationLabel = 'Translations';
    
    protected static ?string $modelLabel = 'Translation';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Translation Information')
                    ->schema([
                        Forms\Components\Select::make('language_id')
                            ->label('Language')
                            ->options(Language::orderBy('name')->pluck('name', 'id'))
                            ->required()
                            ->searchable()
                            ->preload()
                            ->createOptionForm([
                                Forms\Components\TextInput::make('code')
                                    ->required()
                                    ->maxLength(10),
                                Forms\Components\TextInput::make('name')
                                    ->required()
                                    ->maxLength(100),
                                Forms\Components\Toggle::make('is_rtl')
                                    ->default(false),
                            ])
                            ->createOptionUsing(function (array $data): int {
                                return Language::create($data)->getKey();
                            }),
                        Forms\Components\TextInput::make('source_name')
                            ->label('Translator / Edition')
                            ->required()
                            ->maxLength(255)
                            ->placeholder('Sahih International, Muhammad Asad, etc.')
                            ->helperText('Name of the translator or edition'),
                        Forms\Components\TextInput::make('file_name')
                            ->label('Source File')
                            ->maxLength(255)
                            ->placeholder('english-sahih-international.csv')
                            ->helperText('Original CSV filename for reference (optional)'),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('language.name')
                    ->label('Language')
                    ->sortable()
                    ->searchable()
                    ->badge()
                    ->color(fn (Translation $record): string => 
                        $record->language->is_rtl ? 'warning' : 'success'
                    )
                    ->description(fn (Translation $record): string => 
                        $record->language->code . ($record->language->is_rtl ? ' (RTL)' : '')
                    ),
                Tables\Columns\TextColumn::make('source_name')
                    ->label('Translator / Edition')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                Tables\Columns\TextColumn::make('file_name')
                    ->label('Source File')
                    ->searchable()
                    ->toggleable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('verse_texts_count')
                    ->counts('verseTexts')
                    ->label('Verses')
                    ->badge()
                    ->color('primary')
                    ->formatStateUsing(fn (int $state): string => number_format($state)),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('language_id')
                    ->label('Language')
                    ->options(Language::orderBy('name')->pluck('name', 'id'))
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('rtl_only')
                    ->label('RTL Languages Only')
                    ->query(fn ($query) => $query->whereHas('language', fn ($q) => $q->where('is_rtl', true))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Warning: Deleting this translation will also delete all its verse texts!'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('language.name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranTranslations::route('/'),
            'create' => Pages\CreateQuranTranslation::route('/create'),
            'view' => Pages\ViewQuranTranslation::route('/{record}'),
            'edit' => Pages\EditQuranTranslation::route('/{record}/edit'),
        ];
    }
}
