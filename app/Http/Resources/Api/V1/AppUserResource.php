<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class AppUserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'status' => $this->status,
            'name' => $this->profile?->name,
            'email' => $this->providers->first()?->email,
            'avatar' => $this->profile?->avatar, // Assuming avatar is in profile
            'last_active_at' => $this->last_active_at?->toIso8601String(),
            'profile' => new AppUserProfileResource($this->whenLoaded('profile')),
            'providers' => AppUserProviderResource::collection($this->whenLoaded('providers')),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
