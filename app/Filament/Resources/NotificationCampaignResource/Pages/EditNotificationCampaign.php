<?php

namespace App\Filament\Resources\NotificationCampaignResource\Pages;

use App\Filament\Resources\NotificationCampaignResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditNotificationCampaign extends EditRecord
{
    protected static string $resource = NotificationCampaignResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (($data['send_mode'] ?? '') === 'scheduled' && ! empty($data['scheduled_for'])) {
            $data['status'] = 'scheduled';
        }

        return $data;
    }
}
