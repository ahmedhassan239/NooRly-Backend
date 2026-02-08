<?php

namespace App\Filament\Resources;

use App\Domain\QuranAllLang\Models\Language;
use App\Domain\QuranAllLang\Models\QuranVerse;
use App\Domain\QuranAllLang\Models\Translation;
use App\Filament\Resources\QuranAllLangVerseResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class QuranAllLangVerseResource extends Resource
{
    protected static ?string $model = QuranVerse::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';
    
    protected static ?string $navigationGroup = 'Quran All Languages';
    
    protected static ?int $navigationSort = 3;
    
    protected static ?string $navigationLabel = 'Verses';
    
    protected static ?string $modelLabel = 'Verse';
    
    protected static ?string $pluralModelLabel = 'Verses';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Verse Reference')
                    ->schema([
                        Forms\Components\TextInput::make('surah_number')
                            ->label('Surah Number')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(114)
                            ->default(1),
                        Forms\Components\TextInput::make('ayah_number')
                            ->label('Ayah Number')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->default(1),
                        Forms\Components\TextInput::make('ayah_key')
                            ->label('Ayah Key')
                            ->required()
                            ->placeholder('1:1')
                            ->unique(ignoreRecord: true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('surah_name')
                    ->label('Surah')
                    ->sortable(['surah_number'])
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('ayah_number')
                    ->label('Ayah')
                    ->sortable()
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('ayah_key')
                    ->label('Reference')
                    ->searchable()
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('verse_texts_count')
                    ->label('Translations')
                    ->getStateUsing(function (QuranVerse $record): string {
                        $count = $record->verseTexts()->forActiveLanguages()->count();
                        return number_format($count);
                    })
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('preview')
                    ->label('Preview')
                    ->getStateUsing(function (QuranVerse $record): ?string {
                        // Priority: Arabic > first available active language
                        // First try to get Arabic translation
                        $arabicText = $record->verseTexts()
                            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
                            ->join('languages', 'translations.language_id', '=', 'languages.id')
                            ->where('languages.code', 'ar')
                            ->where('languages.is_active', true)
                            ->select('verse_texts.*')
                            ->first();
                        
                        // If Arabic not found, get first available active language
                        if (!$arabicText) {
                            $texts = $record->verseTexts()
                                ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
                                ->join('languages', 'translations.language_id', '=', 'languages.id')
                                ->where('languages.is_active', true)
                                ->select('verse_texts.*')
                                ->orderBy('languages.code')
                                ->first();
                            
                            if ($texts) {
                                $texts->load('translation.language');
                                return \Illuminate\Support\Str::limit($texts->text, 100);
                            }
                            
                            return 'N/A';
                        }
                        
                        // Load relationships manually (cannot use ->with() after JOINs)
                        $arabicText->load('translation.language');
                        return \Illuminate\Support\Str::limit($arabicText->text, 100);
                    })
                    ->wrap()
                    ->extraAttributes(function (QuranVerse $record): array {
                        // Priority: Arabic > first available active language
                        // First try to get Arabic translation
                        $arabicText = $record->verseTexts()
                            ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
                            ->join('languages', 'translations.language_id', '=', 'languages.id')
                            ->where('languages.code', 'ar')
                            ->where('languages.is_active', true)
                            ->select('verse_texts.*')
                            ->first();
                        
                        // If Arabic not found, get first available active language
                        if (!$arabicText) {
                            $texts = $record->verseTexts()
                                ->join('translations', 'verse_texts.translation_id', '=', 'translations.id')
                                ->join('languages', 'translations.language_id', '=', 'languages.id')
                                ->where('languages.is_active', true)
                                ->select('verse_texts.*')
                                ->orderBy('languages.code')
                                ->first();
                            
                            if ($texts) {
                                $texts->load('translation.language');
                                return [
                                    'dir' => $texts->translation->language->is_rtl ? 'rtl' : 'ltr',
                                ];
                            }
                            
                            return ['dir' => 'ltr'];
                        }
                        
                        // Load relationships manually
                        $arabicText->load('translation.language');
                        return [
                            'dir' => $arabicText->translation->language->is_rtl ? 'rtl' : 'ltr',
                        ];
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('surah_number')
                    ->label('Surah')
                    ->options(\App\Domain\QuranAllLang\Helpers\SurahHelper::getSurahNames())
                    ->searchable(),
                Tables\Filters\SelectFilter::make('language')
                    ->label('Language')
                    ->options(Language::active()->orderBy('name')->pluck('name', 'id'))
                    ->query(function (Builder $query, $state) {
                        if ($state['value']) {
                            $query->whereHas('verseTexts', function (Builder $q) use ($state) {
                                $q->whereHas('translation', function (Builder $subQ) use ($state) {
                                    $subQ->where('language_id', $state['value'])
                                         ->whereHas('language', function (Builder $langQ) {
                                             $langQ->where('is_active', true);
                                         });
                                });
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\SelectFilter::make('translation')
                    ->label('Translation')
                    ->options(Translation::forActiveLanguages()
                        ->with('language')
                        ->get()
                        ->mapWithKeys(fn ($t) => [$t->id => "{$t->language->name} - {$t->source_name}"]))
                    ->query(function (Builder $query, $state) {
                        if ($state['value']) {
                            $query->whereHas('verseTexts', function (Builder $q) use ($state) {
                                $q->where('translation_id', $state['value'])
                                  ->forActiveLanguages();
                            });
                        }
                    })
                    ->searchable()
                    ->preload(),
                Tables\Filters\Filter::make('search_text')
                    ->form([
                        Forms\Components\TextInput::make('text')
                            ->label('Search in verse text')
                            ->placeholder('Enter text to search...'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['text'],
                                fn (Builder $query, $text): Builder => $query->searchText($text),
                            );
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([])
            ->modifyQueryUsing(function (Builder $query) {
                // Only show verses that have at least one active language translation
                $query->whereHas('verseTexts', function (Builder $q) {
                    $q->forActiveLanguages();
                });
                
                return $query->orderBy('surah_number')->orderBy('ayah_number');
            })
            ->paginated([25, 50, 100]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranAllLangVerses::route('/'),
            'create' => Pages\CreateQuranAllLangVerse::route('/create'),
            'view' => Pages\ViewQuranAllLangVerse::route('/{record}'),
            'edit' => Pages\EditQuranAllLangVerse::route('/{record}/edit'),
        ];
    }
}
