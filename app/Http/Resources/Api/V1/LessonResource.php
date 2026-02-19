<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class LessonResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * Content is HTML from the TipTap editor. Callout blocks use:
     * .callout .callout-info | .callout-warning | .callout-success
     * Use content_css_url when rendering in WebView to style callouts.
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
            'content_css_url' => url('/css/callout.css'),
            'estimated_minutes' => $this['estimated_minutes'] ?? null,
            'tags' => $this['tags'] ?? [],
            'is_unlocked' => $this['is_unlocked'] ?? false,
            'is_completed' => $this['is_completed'] ?? false,
            'completed_at' => $this['completed_at'] ?? null,
            'reflection_text' => $this['reflection_text'] ?? null,
            'quran_ayahs' => $this['quran_ayahs'] ?? [],
            'hadith_items' => $this['hadith_items'] ?? [],
        ];
    }
}
