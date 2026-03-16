<?php

namespace App\Filament\Resources;

use App\Domain\HelpNow\HelpCategory;
use App\Domain\HelpNow\HelpItem;
use App\Filament\Resources\HelpItemResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class HelpItemResource extends Resource
{
    protected static ?string $model = HelpItem::class;

    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 22;

    protected static ?string $navigationLabel = 'Help Now (Items)';

    protected static ?string $modelLabel = 'Help Item';

    protected static ?string $pluralModelLabel = 'Help Items';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Identity')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->relationship('category', 'title_en')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\TextInput::make('slug')
                            ->label('Slug')
                            ->required()
                            ->maxLength(120)
                            ->live(onBlur: true)
                            ->afterStateUpdated(fn ($state, callable $set) => $set('slug', Str::slug($state))),
                        Forms\Components\TextInput::make('sort_order')->label('Sort Order')->numeric()->default(0),
                        Forms\Components\Toggle::make('is_active')->label('Active')->default(true),
                    ])->columns(2),

                Forms\Components\Section::make('English')
                    ->schema([
                        Forms\Components\TextInput::make('title_en')->label('Title (EN)')->required()->maxLength(255),
                        Forms\Components\TextInput::make('subtitle_en')->label('Subtitle (EN)')->nullable()->maxLength(255),
                        Forms\Components\Textarea::make('content_en')->label('Content (EN)')->required()->rows(12)->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Arabic')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')->label('Title (AR)')->required()->maxLength(255)->extraAttributes(['dir' => 'rtl']),
                        Forms\Components\TextInput::make('subtitle_ar')->label('Subtitle (AR)')->nullable()->maxLength(255)->extraAttributes(['dir' => 'rtl']),
                        Forms\Components\Textarea::make('content_ar')->label('Content (AR)')->required()->rows(12)->columnSpanFull()->extraAttributes(['dir' => 'rtl']),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('category.title_en')->label('Category')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('sort_order')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('slug')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('title_en')->label('Title (EN)')->searchable()->limit(40),
                Tables\Columns\IconColumn::make('is_active')->label('Active')->boolean(),
            ])
            ->defaultSort('sort_order')
            ->reorderable('sort_order')
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'title_en')
                    ->searchable()
                    ->preload(),
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
            'index' => Pages\ListHelpItems::route('/'),
            'create' => Pages\CreateHelpItem::route('/create'),
            'edit' => Pages\EditHelpItem::route('/{record}/edit'),
        ];
    }
}
