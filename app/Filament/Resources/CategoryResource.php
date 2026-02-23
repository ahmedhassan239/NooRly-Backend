<?php

namespace App\Filament\Resources;

use App\Domain\Categories\Models\Category;
use App\Domain\ContentScopes\ContentScope;
use App\Domain\Languages\Language;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\CategoryResource\Pages;
use App\Rules\UniqueTranslatedSlug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\DB;
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
                // Scope selection
                Forms\Components\Section::make('Scope')
                    ->description('Select the content scope for this category')
                    ->schema([
                        Forms\Components\Select::make('scope_id')
                            ->label('Content Scope')
                            ->options(function () {
                                return ContentScope::active()
                                    ->orderBy('label')
                                    ->pluck('label', 'id');
                            })
                            ->searchable()
                            ->preload()
                            ->required()
                            ->live()
                            ->helperText('Categories are organized by scope (e.g., Lessons, Duas, Daily Tasks). Items are assigned to categories from their respective module forms.')
                            ->disabled(function (?Category $record) {
                                // Disable if category has attached items to prevent data inconsistency
                                if (!$record || !$record->id) {
                                    return false;
                                }
                                
                                $hasAttachedItems = DB::table('categorizables')
                                    ->where('category_id', $record->id)
                                    ->exists();
                                
                                return $hasAttachedItems;
                            })
                            ->helperText(function (?Category $record) {
                                if (!$record || !$record->id) {
                                    return 'Categories are organized by scope (e.g., Lessons, Duas, Daily Tasks). Items are assigned to categories from their respective module forms.';
                                }
                                
                                $hasAttachedItems = DB::table('categorizables')
                                    ->where('category_id', $record->id)
                                    ->exists();
                                
                                if ($hasAttachedItems) {
                                    return 'Scope cannot be changed because this category has attached items. Detach all items first to change the scope.';
                                }
                                
                                return 'Categories are organized by scope (e.g., Lessons, Duas, Daily Tasks). Items are assigned to categories from their respective module forms.';
                            }),
                    ]),

                // Icon (for app display: Library Duas/Hadith/Verses/Adhkar category cards)
                Forms\Components\Section::make('Icon')
                    ->description('Icon shown on category cards in the app (e.g. Library > Duas).')
                    ->schema([
                        Forms\Components\Select::make('icon_key')
                            ->label('Icon')
                            ->options(self::getCategoryIconOptions())
                            ->searchable()
                            ->placeholder('None (default icon)')
                            ->helperText('Lucide/Material style key (e.g. moon, book, bed).'),
                        Forms\Components\ColorPicker::make('icon_color')
                            ->label('Icon color (optional)')
                            ->hex()
                            ->nullable()
                            ->dehydrated(fn ($state) => filled($state))
                            ->helperText('Click the preview circle to open the palette. Saved as HEX (e.g. #F59E0B). Leave empty for default.'),
                    ])
                    ->columns(2),

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
            ]);
    }

    /**
     * Predefined icon keys for category cards (Lucide/Material names).
     */
    public static function getCategoryIconOptions(): array
    {
        return [
            'bookmark' => 'Bookmark',
            'moon' => 'Moon',
            'sun' => 'Sun',
            'book' => 'Book',
            'book-open' => 'Book Open',
            'bed' => 'Bed',
            'utensils' => 'Utensils',
            'car' => 'Car',
            'shield' => 'Shield',
            'heart' => 'Heart',
            'star' => 'Star',
            'home' => 'Home',
            'mosque' => 'Mosque (if available)',
            'hand' => 'Hand',
            'sparkles' => 'Sparkles',
            'compass' => 'Compass',
            'clock' => 'Clock',
        ];
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
                Tables\Columns\TextColumn::make('scope.label')
                    ->label('Scope')
                    ->getStateUsing(function (Category $record): string {
                        return $record->scope?->label ?? 'N/A';
                    })
                    ->searchable(query: function ($query, string $search) {
                        return $query->whereHas('scope', function ($q) use ($search) {
                            $q->where('label', 'like', "%{$search}%");
                        });
                    })
                    ->badge()
                    ->color('primary')
                    ->sortable(query: function ($query, string $direction) {
                        return $query->join('content_scopes', 'categories.scope_id', '=', 'content_scopes.id')
                            ->orderBy('content_scopes.label', $direction)
                            ->select('categories.*');
                    }),
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
                Tables\Filters\SelectFilter::make('scope_id')
                    ->label('Scope')
                    ->options(function () {
                        return ContentScope::active()
                            ->orderBy('label')
                            ->pluck('label', 'id');
                    })
                    ->query(function ($query, array $data) {
                        if ($data['value']) {
                            return $query->where('scope_id', $data['value']);
                        }
                        return $query;
                    }),
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
