<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this['id'],
            'day_number' => $this['day_number'],
            'week_number' => $this['week_number'],
            'title' => $this['title'],
            'summary' => $this['summary'] ?? null,
            'content' => $this['content'],
            'estimated_minutes' => $this['estimated_minutes'] ?? null,
            'tags' => $this['tags'] ?? [],
            'is_unlocked' => $this['is_unlocked'] ?? false,
            'is_completed' => $this['is_completed'] ?? false,
            'completed_at' => $this['completed_at'] ?? null,
            'reflection_text' => $this['reflection_text'] ?? null,
        ];
    }
}
