<?php

namespace App\Filament\Resources;

use App\Domain\QuranAllLang\Models\Language;
use App\Filament\Resources\QuranLanguageResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QuranLanguageResource extends Resource
{
    protected static ?string $model = Language::class;

    protected static ?string $navigationIcon = 'heroicon-o-globe-alt';
    
    protected static ?string $navigationGroup = 'Quran All Languages';
    
    protected static ?int $navigationSort = 1;
    
    protected static ?string $navigationLabel = 'Languages';
    
    protected static ?string $modelLabel = 'Language';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Language Information')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(10)
                            ->unique(ignoreRecord: true)
                            ->label('Language Code')
                            ->placeholder('en, ar, bn, zh')
                            ->helperText('ISO 639-1 or ISO 639-2 code'),
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(100)
                            ->label('Language Name')
                            ->placeholder('English, Arabic, Bengali'),
                        Forms\Components\Toggle::make('is_rtl')
                            ->label('Right-to-Left (RTL)')
                            ->helperText('Enable for languages like Arabic, Hebrew, Urdu')
                            ->default(false),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active')
                            ->helperText('Only active languages will be shown in Verses and Translations')
                            ->default(false),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->description(fn (Language $record): string => $record->code),
                Tables\Columns\IconColumn::make('is_rtl')
                    ->label('RTL')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('translations_count')
                    ->counts('translations')
                    ->label('Translations')
                    ->badge()
                    ->color('success'),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_rtl')
                    ->label('Direction')
                    ->placeholder('All directions')
                    ->trueLabel('RTL only')
                    ->falseLabel('LTR only'),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Status')
                    ->placeholder('All languages')
                    ->trueLabel('Active only')
                    ->falseLabel('Inactive only'),
            ])
            ->actions([
                Tables\Actions\Action::make('toggleActive')
                    ->label(fn (Language $record): string => $record->is_active ? 'Deactivate' : 'Activate')
                    ->icon(fn (Language $record): string => $record->is_active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (Language $record): string => $record->is_active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->modalHeading(fn (Language $record): string => $record->is_active ? 'Deactivate Language' : 'Activate Language')
                    ->modalDescription(fn (Language $record): string => 
                        $record->is_active 
                            ? "Are you sure you want to deactivate {$record->name}? This will hide all its translations and verse texts from Verses and Translations pages."
                            : "Are you sure you want to activate {$record->name}? This will make all its translations and verse texts visible in Verses and Translations pages."
                    )
                    ->action(function (Language $record) {
                        // Prevent disabling if it's the last active language
                        $activeCount = Language::active()->count();
                        if ($record->is_active && $activeCount <= 1) {
                            Notification::make()
                                ->title('Cannot deactivate')
                                ->body('At least one language must remain active. Please activate another language first.')
                                ->danger()
                                ->send();
                            return;
                        }

                        $wasActive = $record->is_active;
                        $record->update(['is_active' => !$wasActive]);
                        
                        Notification::make()
                            ->title('Success')
                            ->body("Language {$record->name} has been " . (!$wasActive ? 'activated' : 'deactivated') . '.')
                            ->success()
                            ->send();
                    })
                    ->disabled(function (Language $record): bool {
                        // Only disable if it's the last active language
                        if (!$record->is_active) {
                            return false;
                        }
                        
                        $activeCount = Language::active()->count();
                        return $activeCount <= 1;
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation()
                    ->modalDescription('Warning: Deleting this language will also delete all its translations and verse texts!'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate Selected')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Activate Selected Languages')
                        ->modalDescription('Are you sure you want to activate the selected languages?')
                        ->action(function ($records) {
                            $count = 0;
                            foreach ($records as $record) {
                                /** @var Language $record */
                                if (!$record->is_active) {
                                    $record->update(['is_active' => true]);
                                    $count++;
                                }
                            }
                            
                            Notification::make()
                                ->title('Success')
                                ->body($count > 0 ? "{$count} language(s) have been activated." : 'No changes were made.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate Selected')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->modalHeading('Deactivate Selected Languages')
                        ->modalDescription('Warning: This will hide all translations and verse texts for these languages from Verses and Translations pages. At least one language must remain active.')
                        ->action(function ($records): void {
                            // Filament passes a Collection of selected records
                            if ($records->isEmpty()) {
                                Notification::make()
                                    ->title('No languages selected')
                                    ->body('Please select at least one language to deactivate.')
                                    ->warning()
                                    ->send();
                                return;
                            }

                            // Filter to only active languages in selection
                            $selectedActiveRecords = $records->filter(function (Language $record): bool {
                                return $record->is_active === true;
                            });
                            
                            if ($selectedActiveRecords->isEmpty()) {
                                Notification::make()
                                    ->title('No active languages selected')
                                    ->body('The selected languages are already inactive. No changes were made.')
                                    ->info()
                                    ->send();
                                return;
                            }
                            
                            $activeCount = Language::active()->count();
                            $selectedActiveCount = $selectedActiveRecords->count();
                            
                            // Check if this would disable all languages
                            if ($activeCount - $selectedActiveCount < 1) {
                                Notification::make()
                                    ->title('Cannot deactivate')
                                    ->body('At least one language must remain active. Please leave at least one language active.')
                                    ->danger()
                                    ->send();
                                return;
                            }

                            $count = 0;
                            $selectedActiveRecords->each(function (Language $record) use (&$count): void {
                                if ($record->is_active) {
                                    $record->update(['is_active' => false]);
                                    $count++;
                                }
                            });
                            
                            Notification::make()
                                ->title('Success')
                                ->body($count > 0 ? "{$count} language(s) have been deactivated." : 'No changes were made.')
                                ->success()
                                ->send();
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make()
                        ->requiresConfirmation(),
                ]),
            ])
            ->defaultSort('name');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListQuranLanguages::route('/'),
            'create' => Pages\CreateQuranLanguage::route('/create'),
            'edit' => Pages\EditQuranLanguage::route('/{record}/edit'),
        ];
    }
}
