<?php

namespace App\Filament\Resources;

use App\Domain\Lessons\Lesson;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\LessonResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

class LessonResource extends Resource
{
    use HasTranslatableTabs;
    
    protected static ?string $model = Lesson::class;

    protected static ?string $navigationIcon = 'heroicon-o-academic-cap';
    
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Non-translatable fields
                Forms\Components\Section::make('Lesson Settings')
                    ->schema([
                        Forms\Components\TextInput::make('day_number')
                            ->required()
                            ->numeric()
                            ->label('Day Number')
                            ->minValue(1)
                            ->maxValue(90),
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text',
                                'video' => 'Video',
                            ])
                            ->required()
                            ->default('text'),
                        Forms\Components\TextInput::make('video_url')
                            ->url()
                            ->label('Video URL')
                            ->visible(fn ($get) => $get('type') === 'video'),
                        Forms\Components\TextInput::make('duration_minutes')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->label('Duration (minutes)'),
                    ])
                    ->columns(2),
                
                // Translatable fields in tabs
                static::getTranslationTabs(function ($langCode, $isRequired) {
                    return [
                        Forms\Components\TextInput::make("{$langCode}_title")
                            ->label('Title')
                            ->required($isRequired)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) use ($langCode, $isRequired) {
                                if ($isRequired && empty($state)) {
                                    return;
                                }
                                $set("{$langCode}_slug", \Illuminate\Support\Str::slug($state));
                            }),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make("{$langCode}_slug")
                                    ->label('Slug')
                                    ->required($isRequired)
                                    ->unique(ignoreRecord: true, column: 'slug')
                                    ->disabled(),
                                Forms\Components\Toggle::make("{$langCode}_customize_slug")
                                    ->label('Customize Slug')
                                    ->default(false)
                                    ->reactive()
                                    ->afterStateUpdated(function ($state, callable $set) use ($langCode) {
                                        if ($state) {
                                            $set("{$langCode}_slug_disabled", false);
                                        }
                                    }),
                            ]),
                        
                        Forms\Components\Textarea::make("{$langCode}_short_description")
                            ->label('Short Description')
                            ->maxLength(300)
                            ->rows(3)
                            ->columnSpanFull()
                            ->helperText(fn ($state): string => (300 - strlen($state ?? '')) . ' characters remaining'),
                        
                        TinyEditor::make("{$langCode}_content")
                            ->label('Content')
                            ->required($isRequired)
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('lessons/images')
                            ->profile('default'),
                    ];
                }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('day_number')
                    ->numeric()
                    ->sortable()
                    ->label('Day'),
                Tables\Columns\TextColumn::make('title')
                    ->searchable()
                    ->label('Title (EN)')
                    ->getStateUsing(function ($record) {
                        return $record->translations()->where('language_code', 'en')->first()?->title ?? 'N/A';
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'video' => 'success',
                        'text' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('duration_minutes')
                    ->numeric()
                    ->suffix(' min')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'text' => 'Text',
                        'video' => 'Video',
                    ]),
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
            'index' => Pages\ListLessons::route('/'),
            'create' => Pages\CreateLesson::route('/create'),
            'edit' => Pages\EditLesson::route('/{record}/edit'),
        ];
    }
}
