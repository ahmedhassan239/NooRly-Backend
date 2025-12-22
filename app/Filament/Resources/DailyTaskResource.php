<?php

namespace App\Filament\Resources;

use App\Domain\Tasks\DailyTask;
use App\Filament\Concerns\HasTranslatableTabs;
use App\Filament\Resources\DailyTaskResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DailyTaskResource extends Resource
{
    use HasTranslatableTabs;
    
    protected static ?string $model = DailyTask::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-check';
    
    protected static ?string $navigationGroup = 'Content';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Non-translatable fields
                Forms\Components\Section::make('Task Settings')
                    ->schema([
                        Forms\Components\TextInput::make('day_number')
                            ->required()
                            ->numeric()
                            ->label('Day Number')
                            ->minValue(1)
                            ->maxValue(90),
                        Forms\Components\Select::make('type')
                            ->options([
                                'prayer' => 'Prayer',
                                'action' => 'Action',
                                'read' => 'Read',
                                'sunnah' => 'Sunnah',
                            ])
                            ->required(),
                        Forms\Components\TextInput::make('points')
                            ->required()
                            ->numeric()
                            ->default(10)
                            ->label('Points'),
                    ])
                    ->columns(3),
                
                // Translatable fields in tabs
                static::getTranslationTabs(function ($langCode, $isRequired) {
                    return [
                        Forms\Components\TextInput::make("{$langCode}_title")
                            ->label('Title')
                            ->required($isRequired)
                            ->maxLength(255)
                            ->columnSpanFull(),
                        
                        TinyEditor::make("{$langCode}_description")
                            ->label('Description')
                            ->columnSpanFull()
                            ->fileAttachmentsDisk('public')
                            ->fileAttachmentsDirectory('tasks/images')
                            ->profile('simple'),
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
                    ->label('Title (EN)')
                    ->searchable()
                    ->getStateUsing(function ($record) {
                        return $record->translations()->where('language_code', 'en')->first()?->title ?? 'N/A';
                    }),
                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'prayer' => 'success',
                        'action' => 'info',
                        'read' => 'warning',
                        'sunnah' => 'purple',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('points')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'prayer' => 'Prayer',
                        'action' => 'Action',
                        'read' => 'Read',
                        'sunnah' => 'Sunnah',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDailyTasks::route('/'),
            'create' => Pages\CreateDailyTask::route('/create'),
            'edit' => Pages\EditDailyTask::route('/{record}/edit'),
        ];
    }
}
