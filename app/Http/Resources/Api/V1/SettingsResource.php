<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SettingsResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'language' => $this->language,
            'dark_mode' => $this->dark_mode,
            'notifications_enabled' => $this->notifications_enabled,
            'time_format' => $this->time_format,
            'location_mode' => $this->location_mode,
            'manual_city' => $this->manual_city,
            'manual_country' => $this->manual_country,
            'prayer_calc_method' => $this->prayer_calc_method,
            'prayer_madhab' => $this->prayer_madhab,
            'prayer_adjustments' => $this->prayer_adjustments,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
