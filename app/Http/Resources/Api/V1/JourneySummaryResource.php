<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class JourneySummaryResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $data = $this->resource;

        return [
            'day_index' => $data['day_index'] ?? 1,
            'total_days' => $data['total_days'] ?? 90,
            'streak_days' => $data['streak_days'] ?? 0,
            'active_weeks' => $data['active_weeks'] ?? 0,
            'left_days' => $data['left_days'] ?? 0,
            'completed_lessons' => $data['completed_lessons'] ?? 0,
            'total_lessons' => $data['total_lessons'] ?? 90,
            'completion_percent' => $data['completion_percent'] ?? 0.0,
            'milestones' => $data['milestones'] ?? [],
            'current_lesson' => $data['current_lesson'],
        ];
    }
}
