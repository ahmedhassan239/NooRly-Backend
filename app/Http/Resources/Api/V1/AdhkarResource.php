<?php

namespace App\Http\Resources\Api\V1;

use App\Domain\Adhkar\Adhkar;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes adhkar/dhikr item for API.
 * Resource input: array with 'adhkar' (Adhkar model) and 'is_saved' (bool).
 * Output: id, category_id, repeat_count, source, audio_url, arabic_text, transliteration, english_text, is_saved.
 */
class AdhkarResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $adhkar = $this->resource['adhkar'] ?? $this->resource;
        $isSaved = $this->resource['is_saved'] ?? false;

        if (!$adhkar instanceof Adhkar) {
            return [];
        }

        $locale = $request->header('Accept-Language', 'en');
        if (strlen($locale) >= 2) {
            $locale = strtolower(substr($locale, 0, 2));
        }
        $locale = in_array($locale, ['en', 'ar'], true) ? $locale : 'en';

        $text = $adhkar->text ?? [];
        $reward = $adhkar->reward ?? [];

        return [
            'id' => $adhkar->id,
            'category_id' => $adhkar->category_id,
            'repeat_count' => (int) $adhkar->count,
            'source' => $adhkar->source ?? '',
            'audio_url' => $adhkar->audio_url ?? null,
            'arabic_text' => (string) ($text['ar'] ?? ''),
            'transliteration' => '', // dedicated column can be added later
            'english_text' => (string) ($text['en'] ?? ''),
            'reward' => [
                'ar' => (string) ($reward['ar'] ?? ''),
                'en' => (string) ($reward['en'] ?? ''),
            ],
            'is_saved' => $isSaved,
        ];
    }
}
