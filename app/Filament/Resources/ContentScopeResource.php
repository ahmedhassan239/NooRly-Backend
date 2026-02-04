<?php

namespace App\Filament\Resources;

use App\Domain\ContentScopes\ContentScope;
use App\Filament\Resources\ContentScopeResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

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
                            ->helperText('Unique identifier (e.g., "lessons", "duas"). Must be slug-like.')
                            ->rules([
                                'regex:/^[a-z0-9_]+$/',
                            ])
                            ->live(onBlur: true)
                            ->afterStateUpdated(function ($state, callable $set) {
                                if (empty($state)) {
                                    return;
                                }
                                // Auto-generate label from key if label is empty
                                $set('label', Str::title(str_replace('_', ' ', $state)));
                            }),
                        
                        Forms\Components\TextInput::make('label')
                            ->label('Label')
                            ->required()
                            ->maxLength(255)
                            ->helperText('Human-readable name (e.g., "Lessons")'),
                        
                        Forms\Components\TextInput::make('model_class')
                            ->label('Model Class')
                            ->maxLength(255)
                            ->helperText('Full model class name (e.g., "App\\Domain\\Lessons\\Lesson")')
                            ->placeholder('App\\Domain\\Lessons\\Lesson')
                            ->rules([
                                function () {
                                    return function (string $attribute, $value, \Closure $fail) {
                                        if (!empty($value) && !class_exists($value)) {
                                            $fail('The model class does not exist.');
                                        }
                                    };
                                },
                            ]),
                        
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->default(true)
                            ->helperText('Only active scopes appear in category selection'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('key')
                    ->label('Key')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'),
                
                Tables\Columns\TextColumn::make('label')
                    ->label('Label')
                    ->searchable()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('model_class')
                    ->label('Model Class')
                    ->searchable()
                    ->limit(50)
                    ->tooltip(fn ($record) => $record->model_class)
                    ->color('gray'),
                
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('categories_count')
                    ->label('Categories')
                    ->counts('categories')
                    ->badge()
                    ->color('success'),
                
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
            'index' => Pages\ListContentScopes::route('/'),
            'create' => Pages\CreateContentScope::route('/create'),
            'edit' => Pages\EditContentScope::route('/{record}/edit'),
        ];
    }
}
