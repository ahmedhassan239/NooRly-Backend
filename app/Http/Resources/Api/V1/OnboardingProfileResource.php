<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Users\UserOnboardingProfile;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OnboardingProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @var UserOnboardingProfile $this
     */
    public function toArray(Request $request): array
    {
        return [
            'display_name' => $this->display_name,
            'embrace_islam_range' => $this->embrace_islam_range,
            'arabic_level' => $this->arabic_level,
            'prayer_level' => $this->prayer_level,
            'quran_reading_level' => $this->quran_reading_level,
            'goals' => $this->goals ?? [],
            'challenges' => $this->challenges ?? [],
            'daily_time' => $this->daily_time,
            'preferred_learning_time' => $this->preferred_learning_time,
            'learning_style' => $this->learning_style,
            'reminder_preference' => $this->reminder_preference,
            'islam_date' => $this->islam_date?->toDateString(),
            'onboarding_completed_at' => $this->onboarding_completed_at?->toIso8601String(),
        ];
    }
}
