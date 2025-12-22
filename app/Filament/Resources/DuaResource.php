<?php

namespace App\Filament\Resources;

use App\Domain\Duas\Dua;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\DuaResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

class DuaResource extends Resource
{
    use HasTranslatableTabs;
    
    protected static ?string $model = Dua::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';
    
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Non-translatable field (Arabic text)
                Forms\Components\Section::make('Arabic Text')
                    ->schema([
                        Forms\Components\Textarea::make('arabic')
                            ->label('Arabic Text')
                            ->required()
                            ->rows(3)
                            ->extraInputAttributes(['dir' => 'rtl'])
                            ->helperText('The original Arabic dua text'),
                    ]),
                
                // Translatable fields in tabs
                static::getTranslationTabs(function ($langCode, $isRequired) {
                    return [
                        Forms\Components\TextInput::make("{$langCode}_title")
                            ->label('Title')
                            ->required($isRequired)
                            ->maxLength(255),
                        
                        Forms\Components\Textarea::make("{$langCode}_transliteration")
                            ->label('Transliteration')
                            ->maxLength(500)
                            ->rows(2)
                            ->columnSpanFull()
                            ->helperText('English pronunciation'),
                        
                        TinyEditor::make("{$langCode}_translation_text")
                            ->label('Translation')
                            ->required($isRequired)
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('duas/images')
                            ->profile('simple'),
                        
                        Forms\Components\TextInput::make("{$langCode}_category")
                            ->label('Category')
                            ->maxLength(100)
                            ->helperText('E.g., Daily, Morning, Evening, Travel'),
                    ];
                }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Title (EN)')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->translations()->where('language_code', 'en')->first()?->title ?? 'N/A';
                    }),
                Tables\Columns\TextColumn::make('category')
                    ->label('Category')
                    ->badge()
                    ->getStateUsing(function ($record) {
                        return $record->translations()->where('language_code', 'en')->first()?->category ?? 'N/A';
                    }),
                Tables\Columns\TextColumn::make('arabic')
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
