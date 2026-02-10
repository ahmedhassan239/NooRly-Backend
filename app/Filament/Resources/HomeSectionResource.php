<?php

namespace App\Filament\Resources;

use App\Domain\Home\HomeSection;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class HomeSectionResource extends Resource
{
    protected static ?string $model = HomeSection::class;

    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static ?string $navigationGroup = 'App Configuration';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Home Sections';

    protected static ?string $modelLabel = 'Home Section';

    protected static ?string $pluralModelLabel = 'Home Sections';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Section Identity')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(100)
                            ->placeholder('e.g., daily_verse, featured_duas')
                            ->helperText('Unique identifier for this section'),

                        Forms\Components\Select::make('type')
                            ->label('Display Type')
                            ->required()
                            ->options([
                                'single' => 'Single Item',
                                'list' => 'List',
                                'carousel' => 'Carousel',
                                'banner' => 'Banner',
                                'featured' => 'Featured',
                            ])
                            ->default('list'),

                        Forms\Components\Select::make('source_type')
                            ->label('Content Source')
                            ->options([
                                'verses' => 'Quran Verses',
                                'hadith' => 'Hadith',
                                'duas' => 'Duas',
                                'adhkar' => 'Adhkar',
                                'lessons' => 'Lessons',
                                'daily_tasks' => 'Daily Tasks',
                                'categories' => 'Categories',
                            ])
                            ->searchable(),

                        Forms\Components\TextInput::make('icon')
                            ->label('Icon Name')
                            ->placeholder('e.g., book-open, sun, moon')
                            ->helperText('Heroicon name without prefix'),
                    ])->columns(2),

                Forms\Components\Section::make('Translatable Content')
                    ->schema([
                        Forms\Components\Tabs::make('Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\TextInput::make('title.en')
                                            ->label('Title (English)')
                                            ->required(),
                                        Forms\Components\TextInput::make('subtitle.en')
                                            ->label('Subtitle (English)'),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Arabic')
                                    ->schema([
                                        Forms\Components\TextInput::make('title.ar')
                                            ->label('Title (Arabic)')
                                            ->extraAttributes(['dir' => 'rtl']),
                                        Forms\Components\TextInput::make('subtitle.ar')
                                            ->label('Subtitle (Arabic)')
                                            ->extraAttributes(['dir' => 'rtl']),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Configuration')
                    ->schema([
                        Forms\Components\TextInput::make('route')
                            ->label('Deep Link Route')
                            ->placeholder('/duas, /quran/daily')
                            ->helperText('Route in the Flutter app'),

                        Forms\Components\Textarea::make('query_config')
                            ->label('Query Configuration (JSON)')
                            ->rows(3)
                            ->placeholder('{"featured": true, "limit": 5}')
                            ->helperText('JSON configuration for content filtering')
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state) && !empty($state)) {
                                    return json_decode($state, true);
                                }
                                return $state;
                            })
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                                return $state;
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Select::make('locale')
                            ->label('Locale')
                            ->options([
                                null => 'All Locales',
                                'en' => 'English Only',
                                'ar' => 'Arabic Only',
                            ])
                            ->placeholder('All Locales')
                            ->helperText('Restrict to specific locale'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('position')
                    ->label('#')
                    ->sortable(),

                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('title.en')
                    ->label('Title')
                    ->searchable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'single' => 'info',
                        'list' => 'success',
                        'carousel' => 'warning',
                        'banner' => 'danger',
                        'featured' => 'primary',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('source_type')
                    ->label('Source')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('locale')
                    ->label('Locale')
                    ->default('All')
                    ->badge(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'single' => 'Single Item',
                        'list' => 'List',
                        'carousel' => 'Carousel',
                        'banner' => 'Banner',
                        'featured' => 'Featured',
                    ]),

                Tables\Filters\SelectFilter::make('source_type')
                    ->options([
                        'verses' => 'Quran Verses',
                        'hadith' => 'Hadith',
                        'duas' => 'Duas',
                        'adhkar' => 'Adhkar',
                        'lessons' => 'Lessons',
                        'daily_tasks' => 'Daily Tasks',
                    ]),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('position')
            ->reorderable('position');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\HomeSectionResource\Pages\ListHomeSections::route('/'),
            'create' => \App\Filament\Resources\HomeSectionResource\Pages\CreateHomeSection::route('/create'),
            'edit' => \App\Filament\Resources\HomeSectionResource\Pages\EditHomeSection::route('/{record}/edit'),
        ];
    }
}
