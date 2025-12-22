<?php

namespace App\Filament\Resources;

use App\Domain\Auth\AppUser;
use App\Domain\Auth\Enums\Gender;
use App\Domain\Auth\Enums\MainGoal;
use App\Domain\Auth\Enums\RegistrationMethod;
use App\Domain\Auth\Enums\UserStatus;
use App\Filament\Resources\AppUserResource\Pages;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AppUserResource extends Resource
{
    protected static ?string $model = AppUser::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationLabel = 'App Users';

    protected static ?string $modelLabel = 'App User';

    protected static ?string $pluralModelLabel = 'App Users';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Basic Information')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('email')
                            ->email()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('gender')
                            ->options([
                                Gender::Male->value => 'Male',
                                Gender::Female->value => 'Female',
                            ])
                            ->native(false),
                        Forms\Components\DatePicker::make('date_of_birth'),
                        Forms\Components\DatePicker::make('shahada_date'),
                        Forms\Components\Select::make('main_goal')
                            ->options([
                                MainGoal::Salah->value => 'Salah',
                                MainGoal::QuranBasics->value => 'Quran Basics',
                                MainGoal::FaithEssentials->value => 'Faith Essentials',
                                MainGoal::Exploring->value => 'Exploring',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Location & Settings')
                    ->schema([
                        Forms\Components\TextInput::make('timezone')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('country')
                            ->maxLength(255),
                        Forms\Components\Select::make('status')
                            ->options([
                                UserStatus::Active->value => 'Active',
                                UserStatus::Inactive->value => 'Inactive',
                                UserStatus::Banned->value => 'Banned',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(3),
                Forms\Components\Section::make('Read-Only Information')
                    ->schema([
                        Forms\Components\TextInput::make('registration_method')
                            ->disabled()
                            ->dehydrated(false)
                            ->formatStateUsing(fn ($state) => $state ? ucfirst($state?->value ?? $state) : 'N/A'),
                        Forms\Components\Toggle::make('is_guest')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('created_at')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\DateTimePicker::make('updated_at')
                            ->disabled()
                            ->dehydrated(false),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->searchable()
                    ->sortable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('registration_method')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        RegistrationMethod::Guest->value => 'secondary',
                        RegistrationMethod::Email->value => 'primary',
                        RegistrationMethod::Google->value => 'success',
                        RegistrationMethod::Facebook->value => 'info',
                        RegistrationMethod::Apple->value => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst($state?->value ?? $state ?? 'N/A'))
                    ->sortable(),
                Tables\Columns\IconColumn::make('is_guest')
                    ->boolean()
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state?->value ?? $state) {
                        UserStatus::Active->value => 'success',
                        UserStatus::Inactive->value => 'gray',
                        UserStatus::Banned->value => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst($state?->value ?? $state ?? 'N/A'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('country')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('timezone')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('registration_method')
                    ->options([
                        RegistrationMethod::Guest->value => 'Guest',
                        RegistrationMethod::Email->value => 'Email',
                        RegistrationMethod::Google->value => 'Google',
                        RegistrationMethod::Facebook->value => 'Facebook',
                        RegistrationMethod::Apple->value => 'Apple',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        UserStatus::Active->value => 'Active',
                        UserStatus::Inactive->value => 'Inactive',
                        UserStatus::Banned->value => 'Banned',
                    ])
                    ->native(false),
                Tables\Filters\TernaryFilter::make('is_guest')
                    ->label('Guest User')
                    ->placeholder('All users')
                    ->trueLabel('Guest users only')
                    ->falseLabel('Registered users only'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'Deactivate' : 'Activate')
                    ->icon(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (AppUser $record): void {
                        $record->update([
                            'status' => $record->status === UserStatus::Active ? UserStatus::Inactive : UserStatus::Active,
                        ]);
                    })
                    ->disabled(fn (AppUser $record): bool => $record->status === UserStatus::Banned),
                Tables\Actions\DeleteAction::make()
                    ->requiresConfirmation(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\BulkAction::make('activate')
                        ->label('Activate')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (AppUser $record): void {
                                if ($record->status !== UserStatus::Banned) {
                                    $record->update(['status' => UserStatus::Active]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\BulkAction::make('deactivate')
                        ->label('Deactivate')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (AppUser $record): void {
                                if ($record->status !== UserStatus::Banned) {
                                    $record->update(['status' => UserStatus::Inactive]);
                                }
                            });
                        })
                        ->deselectRecordsAfterCompletion(),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Basic Information')
                    ->schema([
                        Infolists\Components\TextEntry::make('id')
                            ->label('ID'),
                        Infolists\Components\TextEntry::make('name'),
                        Infolists\Components\TextEntry::make('email'),
                        Infolists\Components\TextEntry::make('gender')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
                        Infolists\Components\TextEntry::make('date_of_birth')
                            ->date(),
                        Infolists\Components\TextEntry::make('shahada_date')
                            ->date(),
                        Infolists\Components\TextEntry::make('main_goal')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst(str_replace('_', ' ', $state)) : 'N/A'),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Location & Settings')
                    ->schema([
                        Infolists\Components\TextEntry::make('timezone'),
                        Infolists\Components\TextEntry::make('country')
                            ->default('N/A'),
                        Infolists\Components\IconEntry::make('is_guest')
                            ->boolean()
                            ->label('Guest User'),
                        Infolists\Components\TextEntry::make('registration_method')
                            ->badge()
                            ->color(fn ($state): string => match ($state?->value ?? $state) {
                                RegistrationMethod::Guest->value => 'secondary',
                                RegistrationMethod::Email->value => 'primary',
                                RegistrationMethod::Google->value => 'success',
                                RegistrationMethod::Facebook->value => 'info',
                                RegistrationMethod::Apple->value => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst($state?->value ?? $state ?? 'N/A')),
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state): string => match ($state?->value ?? $state) {
                                UserStatus::Active->value => 'success',
                                UserStatus::Inactive->value => 'gray',
                                UserStatus::Banned->value => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst($state?->value ?? $state ?? 'N/A')),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
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
            'index' => Pages\ListAppUsers::route('/'),
            'view' => Pages\ViewAppUser::route('/{record}'),
            'edit' => Pages\EditAppUser::route('/{record}/edit'),
        ];
    }
}
