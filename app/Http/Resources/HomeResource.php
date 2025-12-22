<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class HomeResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'day_number' => $this->resource['day_number'],
            'prayer_times' => $this->resource['prayer_times'],
            'streak' => $this->resource['streak'],
            'lesson' => new LessonResource($this->resource['lesson']),
            'is_lesson_completed' => $this->resource['lesson_completed'],
            'dua_of_the_day' => $this->resource['dua'] ? [
                'title' => $this->resource['dua']->title,
                'arabic' => $this->resource['dua']->arabic,
                'translation' => $this->resource['dua']->translation,
            ] : null,
            'daily_tasks' => $this->resource['tasks']->map(function ($task) {
                return [
                    'id' => $task->id,
                    'title' => $task->title,
                    'type' => $task->type,
                    'points' => $task->points,
                ];
            }),
        ];
    }
}
