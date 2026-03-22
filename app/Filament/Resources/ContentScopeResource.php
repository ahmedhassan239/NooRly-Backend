<?php

namespace App\Filament\Resources;

use App\Domain\ContentScopes\ContentScope;
use App\Filament\Resources\ContentScopeResource\Pages;
use App\Filament\Support\PublicIconSelect;
use App\Support\Icons\PublicIconsRegistry;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class ContentScopeResource extends Resource
{
    protected static ?string $model = ContentScope::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    
    protected static ?string $navigationGroup = 'Content';
    
    protected static ?int $navigationSort = 0;
    
    protected static ?string $navigationLabel = 'Content Scopes';
    
    protected static ?string $modelLabel = 'Content Scope';
    
    protected static ?string $pluralModelLabel = 'Content Scopes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Scope Information')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->helperText('Unique identifier (e.g., hadith, verses, lessons). Lowercase letters, numbers and underscores only.')
                            ->rules([
                                'regex:/^[a-z0-9_]+$/',
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    return;
                                }
                                // Normalize to slug format (lowercase, spaces to underscores)
                                $normalized = strtolower(Str::slug($state, '_'));
                                if ($normalized !== $state) {
                                    $set('key', $normalized);
                                }
                                // Auto-generate label from key if label is empty
                                $set('label', Str::title(str_replace('_', ' ', $normalized)));
                            }),
                        
                        Forms\Components\TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable name (e.g., "Lessons")'),

                        PublicIconSelect::make('icon_key', 'Icon', false)
                            ->helperText('Icon shown in Library tabs')
                            ->columnSpanFull(),
                        
                        Forms\Components\TextInput::make('model_class')
                            ->label('Model Class')
                            ->maxLength(255)
                            ->helperText('Optional. Leave empty for Library-only scopes (e.g. Hadith, Verses). Otherwise use full class name (e.g. App\\Domain\\Lessons\\Lesson).')
                            ->placeholder('Leave empty or e.g. App\\Domain\\Lessons\\Lesson')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        $value = is_string($value) ? trim($value) : $value;
                                        if (!empty($value) && !class_exists($value)) {
                                            $fail('The model class does not exist.');
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Globally enable or disable this scope in the app and API'),
                        Forms\Components\Toggle::make('show_in_library_tabs')
                            ->label('Show in Library Tabs')
                            ->default(true)
                            ->helperText('When on, this scope appears as a tab in the Library (only if also Active).'),
                        Forms\Components\TextInput::make('display_order')
                            ->label('Display Order')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->helperText('Tab order in Library (you can also reorder by drag on the list).'),
                        Forms\Components\TextInput::make('feature_flag')
                            ->label('Feature flag')
                            ->maxLength(64)
                            ->placeholder('e.g. adhkar')
                            ->helperText('Optional. If set, scope is only shown when config("features.{key}") is true.'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('display_order')
                    ->label('#')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\ImageColumn::make('icon_thumb')
                    ->label('Icon')
                    ->getStateUsing(fn (ContentScope $record): ?string => PublicIconsRegistry::expand($record->icon_key)['icon_url'])
                    ->checkFileExistence(false)
                    ->height(28),
                Tables\Columns\TextColumn::make('icon_key')
                    ->label('Icon key')
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('show_in_library_tabs')
                    ->label('Show in Tabs')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('model_class')
                    ->label('Model Class')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->model_class)
                    ->color('gray')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('feature_flag')
                    ->label('Feature flag')
                    ->placeholder('—')
                    ->toggleable(),
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime('M j, Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->reorderable('display_order')
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active')
                    ->placeholder('All')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('display_order', 'asc');
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
            'index' => Pages\ListContentScopes::route('/'),
            'create' => Pages\CreateContentScope::route('/create'),
            'edit' => Pages\EditContentScope::route('/{record}/edit'),
        ];
    }

}
