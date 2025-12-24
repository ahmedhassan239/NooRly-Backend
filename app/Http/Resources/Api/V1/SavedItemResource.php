<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SavedItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'item_type' => $this->item_type,
            'item_id' => $this->item_id,
            'created_at' => $this->created_at?->toIso8601String(),
            // In a real app, we might want to attach the actual content here
            // but for now, we'll return the metadata.
        ];
    }
}
