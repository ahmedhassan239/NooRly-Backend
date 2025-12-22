<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'day_number' => $this->day_number,
            'title' => $this->title, // Already COALESCE'd by withTranslation()
            'short_description' => $this->short_description ?? null,
            'content' => $this->content,
            'type' => $this->type,
            'video_url' => $this->video_url,
            'duration_minutes' => $this->duration_minutes,
            'resolved_lang' => $this->resolved_lang ?? null,
            'is_completed' => $this->when($request->user('sanctum'), function () use ($request) {
                return $request->user('sanctum')->completedLessons->contains($this->id);
            }, false),
        ];
    }
}
