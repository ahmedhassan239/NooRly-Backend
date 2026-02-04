<?php

namespace App\Filament\Resources;

use App\Domain\Lessons\Lesson;
use App\Domain\Datasets\DatasetVersion;
use App\Filament\Concerns\HasQuranHadithSelects;
use App\Filament\Concerns\HasScopeFilteredCategories;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\LessonResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Storage;
use Mohamedsabil83\FilamentFormsTinyeditor\Components\TinyEditor;

class LessonResource extends Resource
{
    use HasTranslatableTabs, HasScopeFilteredCategories, HasQuranHadithSelects;
    
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

                Forms\Components\Section::make('Categories')
                    ->schema([
                        static::getCategorySelectField('lessons'),
                    ]),

                Forms\Components\Section::make('Quran Ayahs (Ayat)')
                    ->schema([
                        static::getQuranAyahsSelectField(),
                    ]),

                Forms\Components\Section::make('Hadith Items')
                    ->schema([
                        static::getHadithItemsSelectField(),
                    ]),
                
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
                                    ->unique(
                                        table: 'lesson_translations',
                                        column: 'slug',
                                        modifyRuleUsing: function ($rule, $record) use ($langCode) {
                                            $rule->where('language_code', $langCode);
                                            return $record ? $rule->where('lesson_id', '!=', $record->id) : $rule;
                                        }
                                    )
                                    ->disabled(fn ($get) => !$get("{$langCode}_customize_slug")),
                                Forms\Components\Toggle::make("{$langCode}_customize_slug")
                                    ->label('Customize Slug')
                                    ->default(false)
                                    ->live(),
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
            ->headerActions([
                Tables\Actions\Action::make('exportToJson')
                    ->label('Export to JSON')
                    ->icon('heroicon-o-cloud-arrow-down')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function () {
                        $lessons = Lesson::all();
                        
                        $locales = ['en', 'ar'];
                        
                        foreach ($locales as $locale) {
                            $data = $lessons->map(function ($lesson) use ($locale) {
                                $translation = $lesson->translations()->where('language_code', $locale)->first() 
                                    ?? $lesson->translations()->where('language_code', 'en')->first();
                                
                                return [
                                    'id' => 'lesson_' . $lesson->id,
                                    'day_number' => (int)$lesson->day_number,
                                    'week_number' => (int)(($lesson->day_number - 1) / 7) + 1,
                                    'title' => $translation?->title ?? 'N/A',
                                    'summary' => $translation?->short_description ?? '',
                                    'content' => $translation?->content ?? '',
                                    'estimated_minutes' => (int)$lesson->duration_minutes,
                                    'tags' => [], // Placeholder
                                ];
                            })->values()->toArray();

                            $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                            $path = 'content/lessons/' . $locale . '.json';
                            Storage::put($path, $json);
                        }

                        // Update DatasetVersion
                        DatasetVersion::where('dataset_type', 'lessons')->update(['is_current' => false]);
                        
                        DatasetVersion::create([
                            'dataset_type' => 'lessons',
                            'version' => now()->format('YmdHis'),
                            'is_current' => true,
                            'published_at' => now(),
                        ]);

                        Notification::make()
                            ->title('Lessons exported successfully')
                            ->success()
                            ->send();
                    })
            ])
            ->defaultSort('day_number', 'asc');
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
