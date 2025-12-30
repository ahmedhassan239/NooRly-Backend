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

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('dataset_type')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('locale')
                    ->label('Locale')
                    ->required()
                    ->maxLength(5),
                Forms\Components\FileUpload::make('file_path')
                    ->label('File')
                    ->required(),
                Forms\Components\TextInput::make('checksum')
                    ->maxLength(255),
                Forms\Components\Toggle::make('is_published')
                    ->label('Published')
                    ->default(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('dataset_type')
                    ->badge()
                    ->searchable(),
                Tables\Columns\TextColumn::make('version')
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_current')
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
