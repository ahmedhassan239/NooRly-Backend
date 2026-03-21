<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Domain\Notifications\Campaigns\NotificationCampaignService;
use App\Filament\Resources\NotificationCampaignResource;
use Filament\Resources\Pages\CreateRecord;

class CreateNotificationCampaign extends CreateRecord
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        if (($data['send_mode'] ?? '') === 'scheduled') {
            $data['status'] = 'scheduled';
        } else {
            $data['status'] = 'draft';
        }

        return $data;
    }

    protected function afterCreate(): void
    {
        if ($this->record->send_mode === 'now') {
            app(NotificationCampaignService::class)->dispatchProcess($this->record);
        }
    }
}
