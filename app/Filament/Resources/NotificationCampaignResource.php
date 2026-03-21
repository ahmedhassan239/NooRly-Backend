<?php

namespace App\Filament\Resources;

use App\Domain\Notifications\Campaigns\NotificationCampaign;
use App\Domain\Notifications\Campaigns\NotificationCampaignService;
use App\Filament\Resources\NotificationCampaignResource\Pages;
use App\Filament\Resources\NotificationCampaignResource\RelationManagers;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class NotificationCampaignResource extends Resource
{
    protected static ?string $model = NotificationCampaign::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';

    protected static ?string $navigationGroup = 'Engagement';

    protected static ?string $navigationLabel = 'Notification campaigns';

    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Campaign')
                    ->schema([
                        Forms\Components\Select::make('type')
                            ->options(self::typeOptions())
                            ->required()
                            ->native(false),
                        Forms\Components\Select::make('audience_type')
                            ->options(self::audienceOptions())
                            ->required()
                            ->live()
                            ->native(false),
                        Forms\Components\KeyValue::make('audience_filters')
                            ->label('Audience filters')
                            ->helperText('Optional keys: week, language, platform, user_ids (as JSON string in value for arrays)')
                            ->addActionLabel('Add filter')
                            ->nullable(),
                        Forms\Components\TextInput::make('priority')
                            ->maxLength(32),
                        Forms\Components\TextInput::make('route')
                            ->label('Deep link route')
                            ->maxLength(512),
                        Forms\Components\TextInput::make('image_url')
                            ->url()
                            ->maxLength(2048),
                    ])->columns(2),

                Forms\Components\Section::make('Content (Arabic / English)')
                    ->schema([
                        Forms\Components\TextInput::make('title_ar')->maxLength(255)->label('Title (AR)'),
                        Forms\Components\TextInput::make('title_en')->maxLength(255)->label('Title (EN)'),
                        Forms\Components\Textarea::make('body_ar')->rows(4)->label('Body (AR)'),
                        Forms\Components\Textarea::make('body_en')->rows(4)->label('Body (EN)'),
                    ])->columns(2),

                Forms\Components\Section::make('Send')
                    ->schema([
                        Forms\Components\Select::make('send_mode')
                            ->options([
                                'now' => 'Send now (queue after save)',
                                'scheduled' => 'Schedule for later',
                            ])
                            ->required()
                            ->default('now')
                            ->live()
                            ->native(false),
                        Forms\Components\DateTimePicker::make('scheduled_for')
                            ->visible(fn (Forms\Get $get) => $get('send_mode') === 'scheduled')
                            ->seconds(false),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->searchable(),
                Tables\Columns\TextColumn::make('audience_type')->badge()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'sent' => 'success',
                        'partial' => 'warning',
                        'failed', 'cancelled' => 'danger',
                        'processing' => 'info',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('send_mode')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('scheduled_for')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('sent_count')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('failed_count')->numeric()->sortable(),
                Tables\Columns\TextColumn::make('skipped_count')->numeric()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('creator.name')->label('Created by')->toggleable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->sortable(),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'draft' => 'Draft',
                        'scheduled' => 'Scheduled',
                        'processing' => 'Processing',
                        'sent' => 'Sent',
                        'partial' => 'Partial',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('sendNow')
                    ->label('Send now')
                    ->icon('heroicon-o-paper-airplane')
                    ->requiresConfirmation()
                    ->visible(fn (NotificationCampaign $record) => in_array($record->status, ['draft', 'scheduled'], true))
                    ->action(function (NotificationCampaign $record) {
                        if ($record->status === 'scheduled') {
                            $record->update(['send_mode' => 'now', 'scheduled_for' => null]);
                        }
                        app(NotificationCampaignService::class)->dispatchProcess($record->fresh());
                    }),
                Tables\Actions\Action::make('cancelCampaign')
                    ->label('Cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (NotificationCampaign $record) => $record->isCancellable())
                    ->action(fn (NotificationCampaign $record) => app(NotificationCampaignService::class)->cancel($record)),
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make()
                    ->visible(fn (NotificationCampaign $record) => in_array($record->status, ['draft', 'scheduled'], true)),
            ])
            ->bulkActions([]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make('Overview')
                    ->schema([
                        Infolists\Components\TextEntry::make('type'),
                        Infolists\Components\TextEntry::make('audience_type'),
                        Infolists\Components\TextEntry::make('status')->badge(),
                        Infolists\Components\TextEntry::make('send_mode'),
                        Infolists\Components\TextEntry::make('scheduled_for')->dateTime(),
                        Infolists\Components\TextEntry::make('sent_count'),
                        Infolists\Components\TextEntry::make('failed_count'),
                        Infolists\Components\TextEntry::make('skipped_count'),
                        Infolists\Components\TextEntry::make('creator.name')->label('Created by'),
                        Infolists\Components\TextEntry::make('created_at')->dateTime(),
                    ])->columns(2),
                Infolists\Components\Section::make('Content')
                    ->schema([
                        Infolists\Components\TextEntry::make('title_ar')->label('Title AR'),
                        Infolists\Components\TextEntry::make('title_en')->label('Title EN'),
                        Infolists\Components\TextEntry::make('body_ar')->label('Body AR')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('body_en')->label('Body EN')->columnSpanFull(),
                        Infolists\Components\TextEntry::make('route'),
                        Infolists\Components\TextEntry::make('image_url')
                            ->url(fn ($state) => filled($state) ? (string) $state : null)
                            ->openUrlInNewTab(),
                    ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\DeliveriesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNotificationCampaigns::route('/'),
            'create' => Pages\CreateNotificationCampaign::route('/create'),
            'edit' => Pages\EditNotificationCampaign::route('/{record}/edit'),
            'view' => Pages\ViewNotificationCampaign::route('/{record}'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with('creator');
    }

    /**
     * @return array<string, string>
     */
    public static function typeOptions(): array
    {
        return [
            'announcement' => 'General announcement',
            'reminder' => 'Reminder',
            'lesson_reminder' => 'Lesson reminder',
            'friday_reminder' => 'Friday reminder',
            'ramadan_reminder' => 'Ramadan reminder',
            'support_reminder' => 'Support / help reminder',
            'feature_update' => 'Feature update',
            'motivational' => 'Motivational message',
            'onboarding_reminder' => 'Onboarding reminder',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function audienceOptions(): array
    {
        return [
            'all_users' => 'All users',
            'active_users' => 'Active users',
            'inactive_users' => 'Inactive users',
            'notifications_enabled' => 'Notifications enabled (or no settings row)',
            'journey_week' => 'Journey week (filters: {"week":N})',
            'onboarding_incomplete' => 'Onboarding not completed',
            'language' => 'Language (filters: {"language":"ar"|"en"})',
            'platform' => 'Platform token (filters: {"platform":"android"|"ios"|"web"})',
            'selected_users' => 'Selected user IDs (filters: {"user_ids":[1,2]})',
        ];
    }
}
