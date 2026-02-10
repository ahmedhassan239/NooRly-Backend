<?php

namespace App\Filament\Resources;

use App\Domain\AppSettings\AppSetting;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppSettingResource extends Resource
{
    protected static ?string $model = AppSetting::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationGroup = 'App Configuration';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'App Settings';

    protected static ?string $modelLabel = 'Setting';

    protected static ?string $pluralModelLabel = 'App Settings';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Setting Details')
                    ->schema([
                        Forms\Components\TextInput::make('key')
                            ->label('Key')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255)
                            ->placeholder('e.g., app_name, show_daily_verse')
                            ->helperText('Unique identifier for this setting'),

                        Forms\Components\Select::make('group')
                            ->label('Group')
                            ->required()
                            ->options([
                                'general' => 'General',
                                'features' => 'Features',
                                'home' => 'Home Screen',
                                'content' => 'Content',
                                'prayer' => 'Prayer Times',
                                'notifications' => 'Notifications',
                            ])
                            ->default('general'),

                        Forms\Components\Select::make('type')
                            ->label('Value Type')
                            ->required()
                            ->options([
                                'string' => 'String',
                                'boolean' => 'Boolean',
                                'integer' => 'Integer',
                                'array' => 'Array',
                                'json' => 'JSON Object',
                            ])
                            ->default('string')
                            ->live(),

                        Forms\Components\Toggle::make('is_public')
                            ->label('Public (Exposed via API)')
                            ->helperText('If enabled, this setting will be available in the /app-config API endpoint'),
                    ])->columns(2),

                Forms\Components\Section::make('Value')
                    ->schema([
                        // String input
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'string')
                            ->dehydrateStateUsing(fn ($state) => $state),

                        // Boolean toggle
                        Forms\Components\Toggle::make('value')
                            ->label('Value')
                            ->visible(fn (Forms\Get $get) => $get('type') === 'boolean')
                            ->dehydrateStateUsing(fn ($state) => (bool) $state),

                        // Integer input
                        Forms\Components\TextInput::make('value')
                            ->label('Value')
                            ->numeric()
                            ->visible(fn (Forms\Get $get) => $get('type') === 'integer')
                            ->dehydrateStateUsing(fn ($state) => (int) $state),

                        // Array/JSON input
                        Forms\Components\Textarea::make('value')
                            ->label('Value (JSON)')
                            ->visible(fn (Forms\Get $get) => in_array($get('type'), ['array', 'json']))
                            ->rows(5)
                            ->helperText('Enter valid JSON. For arrays: ["item1", "item2"]. For objects: {"key": "value"}')
                            ->dehydrateStateUsing(function ($state) {
                                if (is_string($state)) {
                                    $decoded = json_decode($state, true);
                                    return $decoded !== null ? $decoded : $state;
                                }
                                return $state;
                            })
                            ->formatStateUsing(function ($state) {
                                if (is_array($state)) {
                                    return json_encode($state, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                                }
                                return $state;
                            }),
                    ]),

                Forms\Components\Section::make('Description')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('Description')
                            ->rows(2)
                            ->placeholder('What does this setting control?'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->copyable(),

                Tables\Columns\TextColumn::make('group')
                    ->label('Group')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'general' => 'gray',
                        'features' => 'primary',
                        'home' => 'success',
                        'content' => 'warning',
                        'prayer' => 'info',
                        'notifications' => 'danger',
                        default => 'gray',
                    })
                    ->sortable(),

                Tables\Columns\TextColumn::make('type')
                    ->label('Type')
                    ->badge()
                    ->color('gray'),

                Tables\Columns\TextColumn::make('value')
                    ->label('Value')
                    ->limit(50)
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) {
                            return json_encode($state);
                        }
                        if (is_bool($state)) {
                            return $state ? 'true' : 'false';
                        }
                        return $state;
                    }),

                Tables\Columns\IconColumn::make('is_public')
                    ->label('Public')
                    ->boolean(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('group')
                    ->options([
                        'general' => 'General',
                        'features' => 'Features',
                        'home' => 'Home Screen',
                        'content' => 'Content',
                        'prayer' => 'Prayer Times',
                        'notifications' => 'Notifications',
                    ]),

                Tables\Filters\TernaryFilter::make('is_public')
                    ->label('Public'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('group');
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\Resources\AppSettingResource\Pages\ListAppSettings::route('/'),
            'create' => \App\Filament\Resources\AppSettingResource\Pages\CreateAppSetting::route('/create'),
            'edit' => \App\Filament\Resources\AppSettingResource\Pages\EditAppSetting::route('/{record}/edit'),
        ];
    }
}
