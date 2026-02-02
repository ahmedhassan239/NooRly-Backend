<?php

namespace App\Filament\Resources;

use App\Contracts\HadithSearchServiceInterface;
use App\Contracts\QuranSearchServiceInterface;
use App\Domain\Categories\Models\Category;
use App\Domain\Languages\Language;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\CategoryResource\Pages;
use App\Rules\UniqueTranslatedSlug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class CategoryResource extends Resource
{
    use HasTranslatableTabs;

    protected static ?string $model = Category::class;

    protected static ?string $navigationIcon = 'heroicon-o-tag';
    
    protected static ?string $navigationGroup = 'Religious Content';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Categories';
    
    protected static ?string $modelLabel = 'Category';
    
    protected static ?string $pluralModelLabel = 'Categories';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Translatable fields in tabs
                static::getTranslationTabs(function ($langCode, $isRequired) {
                    $isRtl = in_array($langCode, ['ar', 'fa', 'ur', 'he']);
                    
                    return [
                        Forms\Components\TextInput::make("{$langCode}_name")
                            ->label('Name')
                            ->required($isRequired)
                            ->maxLength(255)
                            ->live(onBlur: true)
                            ->extraInputAttributes(['dir' => $isRtl ? 'rtl' : 'ltr'])
                            ->afterStateUpdated(function ($state, callable $set, $get) use ($langCode) {
                                // Only auto-generate slug if customize_slug is not enabled
                                if (!$get("{$langCode}_customize_slug") && !empty($state)) {
                                    $slug = self::generateSlug($state, $langCode);
                                    $set("{$langCode}_slug", $slug);
                                }
                            }),
                        
                        Forms\Components\Grid::make(2)
                            ->schema([
                                Forms\Components\TextInput::make("{$langCode}_slug")
                                    ->label('Slug')
                                    ->required($isRequired)
                                    ->maxLength(255)
                                    ->extraInputAttributes(['dir' => 'ltr']) // Slugs are always LTR
                                    ->rules(function ($record) use ($langCode) {
                                        return [
                                            UniqueTranslatedSlug::forCategory(
                                                languageCode: $langCode,
                                                excludeId: $record?->id
                                            ),
                                        ];
                                    })
                                    ->disabled(fn ($get) => !$get("{$langCode}_customize_slug"))
                                    ->helperText('Auto-generated from name. Enable "Customize Slug" to edit.'),
                                
                                Forms\Components\Toggle::make("{$langCode}_customize_slug")
                                    ->label('Customize Slug')
                                    ->default(false)
                                    ->live()
                                    ->dehydrated(false), // Don't save this toggle to DB
                            ]),
                        
                        Forms\Components\Textarea::make("{$langCode}_description")
                            ->label('Description')
                            ->rows(3)
                            ->maxLength(1000)
                            ->extraInputAttributes(['dir' => $isRtl ? 'rtl' : 'ltr'])
                            ->columnSpanFull(),
                    ];
                }),

                Forms\Components\Section::make('Quran Verses (Ayat)')
                    ->description('Search and select Quran verses by Arabic text')
                    ->schema([
                        Forms\Components\Select::make('verse_ids')
                            ->label('Quran Verses')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                if (strlen($search) < 2) {
                                    return [];
                                }
                                $service = app(QuranSearchServiceInterface::class);
                                return $service->searchArabicVerses($search, 50);
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                if (empty($values)) {
                                    return [];
                                }
                                $service = app(QuranSearchServiceInterface::class);
                                return $service->getVerseLabels($values);
                            })
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?Category $record) {
                                if ($record) {
                                    $component->state($record->getVerseIds());
                                }
                            })
                            ->searchPrompt('Type at least 2 characters to search...')
                            ->searchingMessage('Searching verses...')
                            ->noSearchResultsMessage('No verses found')
                            ->helperText('Search by Arabic text. Results show verse reference and text preview.')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make('Hadith')
                    ->description('Search and select Hadith by Arabic text')
                    ->schema([
                        Forms\Components\Select::make('hadith_ids')
                            ->label('Hadith Items')
                            ->multiple()
                            ->searchable()
                            ->getSearchResultsUsing(function (string $search): array {
                                if (strlen($search) < 2) {
                                    return [];
                                }
                                $service = app(HadithSearchServiceInterface::class);
                                return $service->searchArabicHadith($search, 50);
                            })
                            ->getOptionLabelsUsing(function (array $values): array {
                                if (empty($values)) {
                                    return [];
                                }
                                $service = app(HadithSearchServiceInterface::class);
                                return $service->getHadithLabels($values);
                            })
                            ->afterStateHydrated(function (Forms\Components\Select $component, ?Category $record) {
                                if ($record) {
                                    $component->state($record->getHadithIds());
                                }
                            })
                            ->searchPrompt('Type at least 2 characters to search...')
                            ->searchingMessage('Searching hadith...')
                            ->noSearchResultsMessage('No hadith found')
                            ->helperText('Search by Arabic text. Results show source, number, and text preview.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * Generate a slug from name for a specific language.
     */
    protected static function generateSlug(string $name, string $languageCode): string
    {
        // For Arabic and RTL languages, create a URL-safe slug preserving Arabic chars
        if (in_array($languageCode, ['ar', 'fa', 'ur', 'he'])) {
            $slug = preg_replace('/\s+/', ' ', trim($name));
            $slug = str_replace(' ', '-', $slug);
            $slug = preg_replace('/[^\p{Arabic}a-zA-Z0-9\-]/u', '', $slug);
            $slug = preg_replace('/-+/', '-', $slug);
            return trim($slug, '-');
        }

        return Str::slug($name);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Name')
                    ->getStateUsing(function (Category $record): string {
                        // Show name in current locale or English
                        return $record->getName() ?? 'N/A';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('translations', function ($q) use ($search) {
                            $q->where('name', 'like', "%{$search}%");
                        });
                    })
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->getStateUsing(function (Category $record): string {
                        return $record->getSlug() ?? 'N/A';
                    })
                    ->color('gray'),
                Tables\Columns\TextColumn::make('translations_count')
                    ->label('Languages')
                    ->getStateUsing(fn (Category $record): int => $record->translations()->count())
                    ->badge()
                    ->color('warning'),
                Tables\Columns\TextColumn::make('verses_count')
                    ->label('Ayat')
                    ->getStateUsing(fn (Category $record): int => $record->verses_count)
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('hadiths_count')
                    ->label('Hadith')
                    ->getStateUsing(fn (Category $record): int => count($record->getHadithIds()))
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('has_verses')
                    ->label('Has Verses')
                    ->query(fn ($query) => $query->whereExists(function ($q) {
                        $q->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('category_verse')
                          ->whereColumn('category_verse.category_id', 'categories.id');
                    })),
                Tables\Filters\Filter::make('has_hadiths')
                    ->label('Has Hadith')
                    ->query(fn ($query) => $query->whereExists(function ($q) {
                        $q->select(\Illuminate\Support\Facades\DB::raw(1))
                          ->from('category_hadith')
                          ->whereColumn('category_hadith.category_id', 'categories.id');
                    })),
                Tables\Filters\SelectFilter::make('language')
                    ->label('Has Translation')
                    ->options(Language::active()->pluck('name', 'code'))
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            return $query->whereHas('translations', function ($q) use ($data) {
                                $q->where('language_code', $data['value']);
                            });
                        }
                        return $query;
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCategories::route('/'),
            'create' => Pages\CreateCategory::route('/create'),
            'view' => Pages\ViewCategory::route('/{record}'),
            'edit' => Pages\EditCategory::route('/{record}/edit'),
        ];
    }
}
