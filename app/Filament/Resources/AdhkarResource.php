<?php

namespace App\Filament\Resources;

use App\Domain\Adhkar\Adhkar;
use App\Domain\Categories\Models\Category;
use App\Domain\ContentScopes\ContentScope;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class AdhkarResource extends Resource
{
    protected static ?string $model = Adhkar::class;

    protected static ?string $navigationIcon = 'heroicon-o-hand-raised';

    protected static ?string $navigationGroup = 'Content';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Adhkar';

    protected static ?string $modelLabel = 'Dhikr';

    protected static ?string $pluralModelLabel = 'Adhkar';

    /** Scope key for this resource; used to filter categories. */
    protected static string $scopeKey = 'adhkar';

    /**
     * Get categories for this resource's scope (for dropdown options).
     */
    public static function getCategoriesForScope(): \Illuminate\Support\Collection
    {
        $scope = ContentScope::where('key', static::$scopeKey)->first();
        if (!$scope) {
            return collect();
        }
        return Category::byScope($scope->id)
            ->with('translations')
            ->get();
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Translatable Content')
                    ->schema([
                        Forms\Components\Tabs::make('Translations')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('English')
                                    ->schema([
                                        Forms\Components\Textarea::make('text.en')
                                            ->label('Text (English)')
                                            ->rows(4),
                                        Forms\Components\Textarea::make('reward.en')
                                            ->label('Reward (English)')
                                            ->rows(2),
                                    ]),
                                Forms\Components\Tabs\Tab::make('Arabic')
                                    ->schema([
                                        Forms\Components\Textarea::make('text.ar')
                                            ->label('Text (Arabic)')
                                            ->rows(4)
                                            ->extraAttributes(['dir' => 'rtl'])
                                            ->required(),
                                        Forms\Components\Textarea::make('reward.ar')
                                            ->label('Reward (Arabic)')
                                            ->rows(2)
                                            ->extraAttributes(['dir' => 'rtl']),
                                    ]),
                            ]),
                    ]),

                Forms\Components\Section::make('Classification')
                    ->schema([
                        Forms\Components\Select::make('category_id')
                            ->label('Category')
                            ->options(function () {
                                return static::getCategoriesForScope()
                                    ->mapWithKeys(fn ($cat) => [
                                        $cat->id => (string) ($cat->getName() ?? "Category #{$cat->id}"),
                                    ])
                                    ->all();
                            })
                            ->searchable()
                            ->preload()
                            ->required(),

                        Forms\Components\TextInput::make('count')
                            ->label('Repeat Count')
                            ->numeric()
                            ->default(1)
                            ->minValue(1)
                            ->helperText('How many times to repeat this dhikr'),

                        Forms\Components\TextInput::make('source')
                            ->label('Source')
                            ->placeholder('e.g., Sahih Bukhari, Sahih Muslim')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Display Settings')
                    ->schema([
                        Forms\Components\TextInput::make('position')
                            ->label('Position')
                            ->numeric()
                            ->default(0)
                            ->helperText('Lower numbers appear first'),

                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true),

                        Forms\Components\Toggle::make('is_featured')
                            ->label('Featured')
                            ->default(false),
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

                Tables\Columns\TextColumn::make('text.ar')
                    ->label('Text (Arabic)')
                    ->searchable()
                    ->limit(40)
                    ->extraAttributes(['dir' => 'rtl']),

                Tables\Columns\TextColumn::make('text.en')
                    ->label('Text (English)')
                    ->limit(40),

                Tables\Columns\TextColumn::make('category_id')
                    ->label('Category')
                    ->formatStateUsing(fn ($record) => $record->category?->getName() ?? '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('count')
                    ->label('Count')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\IconColumn::make('is_featured')
                    ->label('Featured')
                    ->boolean(),

                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label('Category')
                    ->relationship('category', 'id', modifyQueryUsing: fn ($query) => $query->whereHas('scope', fn ($q) => $q->where('key', static::$scopeKey)))
                    ->getOptionLabelFromRecordUsing(fn ($record) => $record->getName() ?? "Category #{$record->id}"),

                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active'),

                Tables\Filters\TernaryFilter::make('is_featured')
                    ->label('Featured'),
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

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('category');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\AdhkarResource\Pages\ListAdhkar::route('/'),
            'create' => \App\Filament\Resources\AdhkarResource\Pages\CreateAdhkar::route('/create'),
            'edit' => \App\Filament\Resources\AdhkarResource\Pages\EditAdhkar::route('/{record}/edit'),
        ];
    }
}
