<?php

namespace App\Filament\Resources;

use App\Domain\Hadith\Models\HadithItem;
use App\Filament\Resources\HadithItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HadithItemResource extends Resource
{
    protected static ?string $model = HadithItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    
    protected static ?string $navigationGroup = 'Religious Content';
    
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Reference')
                    ->schema([
                        Forms\Components\TextInput::make('source')
                            ->label('Collection')
                            ->required(),
                        Forms\Components\TextInput::make('chapter_no')
                            ->label('Book Number')
                            ->numeric(),
                        Forms\Components\TextInput::make('hadith_no')
                            ->label('Hadith Number')
                            ->numeric(),
                    ])->columns(3),

                Forms\Components\Tabs::make('Content')
                    ->tabs([
                        Forms\Components\Tabs\Tab::make('Arabic')
                            ->schema([
                                Forms\Components\Textarea::make('text_ar')
                                    ->label('Text')
                                    ->rows(5)
                                    ->extraInputAttributes(['dir' => 'rtl']),
                            ]),
                        Forms\Components\Tabs\Tab::make('English')
                            ->schema([
                                Forms\Components\Textarea::make('text_en')
                                    ->label('Translation')
                                    ->rows(5),
                            ]),
                    ])->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('source')
                    ->label('Collection')
                    ->badge()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('chapter_no')
                    ->label('Book #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('hadith_no')
                    ->label('Hadith #')
                    ->sortable(),
                Tables\Columns\TextColumn::make('text_ar')
                    ->label('Arabic')
                    ->limit(50)
                    ->extraAttributes(['dir' => 'rtl'])
                    ->searchable(),
                Tables\Columns\TextColumn::make('text_en')
                    ->label('English')
                    ->limit(50)
                    ->searchable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('source')
                    ->label('Collection')
                    ->options(function () {
                        return \App\Domain\Hadith\Models\HadithItem::distinct()->pluck('source', 'source')->toArray();
                    }),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->visible(fn () => config('content.admin_allow_delete', false)),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListHadithItems::route('/'),
            'create' => Pages\CreateHadithItem::route('/create'),
            'edit' => Pages\EditHadithItem::route('/{record}/edit'),
        ];
    }
}
