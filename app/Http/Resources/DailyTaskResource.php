<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class DailyTaskResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'title' => $this->title, // Already COALESCE'd by withTranslation()
            'description' => $this->description ?? null,
            'type' => $this->type,
            'points' => $this->points,
            'resolved_lang' => $this->resolved_lang ?? null,
            'is_completed' => $this->when($request->user('sanctum'), function () use ($request) {
                return $request->user('sanctum')->completedTasks->contains($this->id);
            }, false),
        ];
    }
}
