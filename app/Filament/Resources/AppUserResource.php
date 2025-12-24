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
                        Forms\Components\TextInput::make('profile.name')
                            ->label('Name')
                            ->maxLength(255),
                        Forms\Components\Select::make('profile.gender')
                            ->label('Gender')
                            ->options([
                                'male' => 'Male',
                                'female' => 'Female',
                                'other' => 'Other',
                                'unknown' => 'Unknown',
                            ])
                            ->native(false),
                        Forms\Components\DatePicker::make('profile.birth_date')
                            ->label('Birth Date'),
                        Forms\Components\Select::make('profile.locale')
                            ->label('Locale')
                            ->options([
                                'en' => 'English',
                                'ar' => 'Arabic',
                            ])
                            ->native(false),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Account Settings')
                    ->schema([
                        Forms\Components\TextInput::make('uuid')
                            ->disabled()
                            ->label('UUID'),
                        Forms\Components\Select::make('status')
                            ->options([
                                'active' => 'Active',
                                'disabled' => 'Disabled',
                                'banned' => 'Banned',
                            ])
                            ->required()
                            ->native(false),
                    ])
                    ->columns(2),
                Forms\Components\Section::make('Metadata')
                    ->schema([
                        Forms\Components\DateTimePicker::make('last_active_at')
                            ->disabled()
                            ->label('Last Active At'),
                        Forms\Components\DateTimePicker::make('created_at')
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
                Tables\Columns\TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('profile.name')
                    ->label('Name')
                    ->searchable()
                    ->sortable()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('providers.email')
                    ->label('Emails')
                    ->searchable()
                    ->sortable()
                    ->wrap()
                    ->default('N/A'),
                Tables\Columns\TextColumn::make('providers.provider')
                    ->label('Providers')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'guest' => 'secondary',
                        'email' => 'primary',
                        'google' => 'success',
                        'facebook' => 'info',
                        'apple' => 'warning',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst($state))
                    ->separator(', '),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => match ($state) {
                        'active' => 'success',
                        'disabled' => 'gray',
                        'banned' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state): string => ucfirst($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('last_active_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('provider')
                    ->relationship('providers', 'provider')
                    ->options([
                        'guest' => 'Guest',
                        'email' => 'Email',
                        'google' => 'Google',
                        'facebook' => 'Facebook',
                        'apple' => 'Apple',
                    ])
                    ->native(false),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'active' => 'Active',
                        'disabled' => 'Disabled',
                        'banned' => 'Banned',
                    ])
                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggleStatus')
                    ->label(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'Disable' : 'Activate')
                    ->icon(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle')
                    ->color(fn (AppUser $record): string => $record->status === UserStatus::Active ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (AppUser $record): void {
                        $record->update([
                            'status' => $record->status === UserStatus::Active ? UserStatus::Disabled : UserStatus::Active,
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
                        ->label('Disable')
                        ->icon('heroicon-o-x-circle')
                        ->color('warning')
                        ->requiresConfirmation()
                        ->action(function ($records): void {
                            $records->each(function (AppUser $record): void {
                                if ($record->status !== UserStatus::Banned) {
                                    $record->update(['status' => UserStatus::Disabled]);
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
                        Infolists\Components\TextEntry::make('uuid')
                            ->label('UUID'),
                        Infolists\Components\TextEntry::make('profile.name')
                            ->label('Name'),
                        Infolists\Components\TextEntry::make('profile.gender')
                            ->label('Gender')
                            ->formatStateUsing(fn (?string $state): string => $state ? ucfirst($state) : 'N/A'),
                        Infolists\Components\TextEntry::make('profile.birth_date')
                            ->label('Birth Date')
                            ->date(),
                        Infolists\Components\TextEntry::make('profile.locale')
                            ->label('Locale')
                            ->formatStateUsing(fn (?string $state): string => strtoupper($state ?? 'EN')),
                    ])
                    ->columns(2),
                Infolists\Components\Section::make('Account & Providers')
                    ->schema([
                        Infolists\Components\TextEntry::make('status')
                            ->badge()
                            ->color(fn ($state): string => match ($state) {
                                'active' => 'success',
                                'disabled' => 'gray',
                                'banned' => 'danger',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst($state)),
                        Infolists\Components\TextEntry::make('providers.provider')
                            ->label('Auth Providers')
                            ->badge()
                            ->color(fn ($state): string => match ($state) {
                                'guest' => 'secondary',
                                'email' => 'primary',
                                'google' => 'success',
                                'facebook' => 'info',
                                'apple' => 'warning',
                                default => 'gray',
                            })
                            ->formatStateUsing(fn ($state): string => ucfirst($state))
                            ->separator(', '),
                        Infolists\Components\TextEntry::make('providers.email')
                            ->label('Registered Emails')
                            ->listWithLineBreaks()
                            ->bulleted(),
                    ])
                    ->columns(3),
                Infolists\Components\Section::make('App Usage')
                    ->schema([
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('onboarding.start_date')
                                ->label('Journey Start')
                                ->date(),
                            Infolists\Components\TextEntry::make('onboarding.shahada_date')
                                ->label('Shahada Date')
                                ->date()
                                ->placeholder('Not set'),
                            Infolists\Components\TextEntry::make('onboarding.learning_goal')
                                ->label('Goal')
                                ->placeholder('None'),
                            Infolists\Components\TextEntry::make('onboarding.timezone')
                                ->label('Timezone'),
                        ])->columns(2)->label('Onboarding Status'),

                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('settings.language')
                                ->label('Language')
                                ->formatStateUsing(fn (?string $state): string => strtoupper($state ?? 'EN')),
                            Infolists\Components\IconEntry::make('settings.dark_mode')
                                ->label('Dark Mode')
                                ->boolean(),
                            Infolists\Components\IconEntry::make('settings.notifications_enabled')
                                ->label('Notifications')
                                ->boolean(),
                            Infolists\Components\TextEntry::make('settings.location_mode')
                                ->label('Location')
                                ->formatStateUsing(fn (?string $state): string => ucfirst($state ?? 'GPS')),
                        ])->columns(2)->label('Preferences'),
                    ])
                    ->columns(2),

                Infolists\Components\Section::make('Timestamps')
                    ->schema([
                        Infolists\Components\TextEntry::make('last_active_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('created_at')
                            ->dateTime(),
                        Infolists\Components\TextEntry::make('updated_at')
                            ->dateTime(),
                    ])
                    ->columns(3)
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
