<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppUserProfileResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'name' => $this->name,
            'avatar' => $this->avatar,
            'avatar_url' => $this->resolveAvatarUrl($this->avatar),
            'gender' => $this->gender,
            'birth_date' => $this->birth_date?->toDateString(),
            'locale' => $this->locale,
        ];
    }

    private function resolveAvatarUrl(?string $avatarPath): ?string
    {
        if (!$avatarPath) {
            return null;
        }

        if (str_starts_with($avatarPath, 'http://') || str_starts_with($avatarPath, 'https://')) {
            return $avatarPath;
        }

        return Storage::disk('public')->url($avatarPath);
    }
}
