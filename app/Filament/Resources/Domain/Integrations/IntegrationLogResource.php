<?php

namespace App\Filament\Resources\Domain\Integrations;

use App\Domain\Integrations\IntegrationLog;
use App\Filament\Resources\Domain\Integrations\IntegrationLogResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class IntegrationLogResource extends Resource
{
    protected static ?string $model = IntegrationLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';
    
    protected static ?string $navigationGroup = 'Systems';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('provider')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('endpoint')
                    ->limit(50)
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => $state === 'success' ? 'success' : 'danger'),
                Tables\Columns\TextColumn::make('http_code')
                    ->label('HTTP')
                    ->sortable(),
                Tables\Columns\TextColumn::make('duration_ms')
                    ->label('Duration')
                    ->suffix(' ms')
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
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
            'index' => Pages\ManageIntegrationLogs::route('/'),
        ];
    }
}
