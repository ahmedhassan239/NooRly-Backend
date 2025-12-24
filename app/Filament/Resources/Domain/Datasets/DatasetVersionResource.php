<?php

namespace App\Filament\Resources\Domain\Datasets;

use App\Domain\Datasets\DatasetVersion;
use App\Filament\Resources\Domain\Datasets\DatasetVersionResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DatasetVersionResource extends Resource
{
    protected static ?string $model = DatasetVersion::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';
    
    protected static ?string $navigationGroup = 'Systems';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dataset_type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                Tables\Columns\IconEntry::make('is_current')
                    ->boolean()
                    ->label('Current'),
                Tables\Columns\TextColumn::make('checksum')
                    ->limit(10)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('published_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_current')
                    ->label('Current Version Only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
            'index' => Pages\ManageDatasetVersions::route('/'),
        ];
    }
}
