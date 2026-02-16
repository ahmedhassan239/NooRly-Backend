<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Users\AppUserOnboarding;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     * Full onboarding state: shahada_date, goals, current_step, completed.
     */
    public function toArray(Request $request): array
    {
        /** @var AppUserOnboarding $this */
        $completed = $this->completed_at !== null;

        return [
            'shahada_date' => $this->shahada_date?->toDateString(),
            'goals' => $this->goals ?? [],
            'summary_completed' => (bool) ($this->summary_completed ?? false),
            'current_step' => $this->current_step ?? AppUserOnboarding::STEP_SHAHADA_DATE,
            'completed' => $completed,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'start_date' => $this->start_date?->toDateString(),
            'learning_goal' => $this->learning_goal,
            'timezone' => $this->timezone,
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
