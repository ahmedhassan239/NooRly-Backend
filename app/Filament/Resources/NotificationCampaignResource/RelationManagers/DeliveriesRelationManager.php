<?php

namespace App\Filament\Resources\NotificationCampaignResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DeliveriesRelationManager extends RelationManager
{
    protected static string $relationship = 'deliveries';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('user_id')->label('App user'),
                Tables\Columns\TextColumn::make('platform'),
                Tables\Columns\TextColumn::make('provider'),
                Tables\Columns\TextColumn::make('delivery_status')->badge(),
                Tables\Columns\TextColumn::make('failure_reason')->limit(40)->wrap(),
                Tables\Columns\TextColumn::make('sent_at')->dateTime(),
            ])
            ->defaultSort('id', 'desc');
    }
}
