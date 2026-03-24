<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        $emailProvider = $this->providers->firstWhere('provider', 'email');
        $email = $emailProvider?->email ?? $this->providers->first()?->email;

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'name' => $this->profile?->name,
            'email' => $email,
            'gender' => $this->profile?->gender,
            'birth_date' => $this->profile?->birth_date?->toDateString(),
            'locale' => $this->profile?->locale,
            'avatar' => $this->profile?->avatar,
            'avatar_url' => $this->resolveAvatarUrl($this->profile?->avatar),
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'profile' => new AppUserProfileResource($this->whenLoaded('profile')),
            'providers' => AppUserProviderResource::collection($this->whenLoaded('providers')),
            'onboarding' => $this->when($this->relationLoaded('onboarding') && $this->onboarding, function () {
                return [
                    'completed' => $this->onboarding->completed_at !== null,
                    'current_step' => $this->onboarding->current_step ?? 'shahada_date',
                ];
            }),
            'created_at' => $this->created_at?->toIso8601String(),
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
