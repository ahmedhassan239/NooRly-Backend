<?php

namespace App\Filament\Resources;

use App\Domain\Lessons\Lesson;
use App\Domain\Datasets\DatasetVersion;
use App\Filament\Concerns\HasQuranHadithSelects;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\LessonResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use FilamentTiptapEditor\TiptapEditor;
use Illuminate\Support\Facades\Storage;

class LessonResource extends Resource
{
    use HasTranslatableTabs, HasQuranHadithSelects;
    
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
                        Forms\Components\Select::make('type')
                            ->options([
                                'text' => 'Text',
                                'video' => 'Video',
                                'week_reflection' => 'Week Reflection',
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
                            ->afterStateUpdated(function ($state, callable $set, callable $get) use ($langCode, $isRequired) {
                                if ($isRequired && empty($state)) {
                                    return;
                                }
                                if (! $get("{$langCode}_customize_slug")) {
                                    $set("{$langCode}_slug", \Illuminate\Support\Str::slug($state));
                                }
                            }),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make("{$langCode}_slug")
                                    ->label('Slug')
                                    ->required($isRequired)
                                    ->unique(
                                        table: 'lesson_translations',
                                        column: 'slug',
                                        ignorable: fn ($component) => $component->getRecord()?->translations()->where('language_code', $langCode)->first(),
                                        modifyRuleUsing: fn ($rule) => $rule->where('language_code', $langCode)
                                    )
                                    ->disabled(fn ($get) => ! $get("{$langCode}_customize_slug")),
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
                        
                        TiptapEditor::make("{$langCode}_content")
                            ->label('Content')
                            ->required($isRequired)
                            ->columnSpanFull()
                            ->profile('default')
                            ->disk('public')
                            ->directory('lessons/images'),
                    ];
                }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
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
                        'week_reflection' => 'warning',
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
                        'week_reflection' => 'Week Reflection',
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
                        $locales = ['en', 'ar'];
                        // Build journey order: first occurrence of each lesson gets global day 1, 2, 3...
                        $ordered = \App\Domain\Journey\JourneyWeekLesson::orderBy('journey_week_id')->orderBy('sort_order')->get();
                        $globalDayByLessonId = [];
                        $day = 1;
                        foreach ($ordered as $row) {
                            if (! isset($globalDayByLessonId[$row->lesson_id])) {
                                $globalDayByLessonId[$row->lesson_id] = $day++;
                            }
                        }
                        $lessonIds = array_keys($globalDayByLessonId);
                        $lessons = $lessonIds ? Lesson::with('translations')->whereIn('id', $lessonIds)->get()->keyBy('id') : collect();

                        foreach ($locales as $locale) {
                            $data = collect($lessonIds)->map(function ($id) use ($lessons, $locale, $globalDayByLessonId) {
                                $lesson = $lessons->get($id);
                                if (! $lesson) {
                                    return null;
                                }
                                $translation = $lesson->translations()->where('language_code', $locale)->first()
                                    ?? $lesson->translations()->where('language_code', 'en')->first();
                                $dayNum = $globalDayByLessonId[$id];
                                return [
                                    'id' => 'lesson_' . $lesson->id,
                                    'day_number' => (int) $dayNum,
                                    'week_number' => (int) (($dayNum - 1) / 7) + 1,
                                    'title' => $translation?->title ?? 'N/A',
                                    'summary' => $translation?->short_description ?? '',
                                    'content' => $translation?->content ?? '',
                                    'estimated_minutes' => (int) $lesson->duration_minutes,
                                    'tags' => [],
                                ];
                            })->filter()->values()->toArray();

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
            ->defaultSort('created_at', 'asc');
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
