<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Domain\Notifications\Campaigns\NotificationCampaignService;
use App\Filament\Resources\NotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewNotificationCampaign extends ViewRecord
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('sendNow')
                ->label('Send now')
                ->icon('heroicon-o-paper-airplane')
                ->requiresConfirmation()
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled'], true))
                ->action(function () {
                    if ($this->record->status === 'scheduled') {
                        $this->record->update(['send_mode' => 'now', 'scheduled_for' => null]);
                    }
                    app(NotificationCampaignService::class)->dispatchProcess($this->record->fresh());
                }),
            Actions\Action::make('cancel')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn () => $this->record->isCancellable())
                ->action(fn () => app(NotificationCampaignService::class)->cancel($this->record)),
            Actions\EditAction::make()
                ->visible(fn () => in_array($this->record->status, ['draft', 'scheduled'], true)),
        ];
    }
}
