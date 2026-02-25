<?php

namespace App\Filament\Resources\AdhkarResource\Pages;

use App\Filament\Resources\AdhkarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAdhkar extends EditRecord
{
    protected static string $resource = AdhkarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }

    /**
     * Reset category_id if it is not valid for this resource's scope
     * (e.g. category belongs to another scope). Ensures edit form never
     * shows an invalid selection.
     */
    public function mutateFormDataBeforeFill(array $data): array
    {
        $validIds = AdhkarResource::getCategoriesForScope()->pluck('id')->all();
        if (!empty($data['category_id']) && !in_array((int) $data['category_id'], $validIds, true)) {
            $data['category_id'] = null;
        }
        return $data;
    }
}
