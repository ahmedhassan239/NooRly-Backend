<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'start_date' => $this->start_date?->toDateString(),
            'shahada_date' => $this->shahada_date?->toDateString(),
            'learning_goal' => $this->learning_goal,
            'timezone' => $this->timezone,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
