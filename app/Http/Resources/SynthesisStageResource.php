<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use App\Services\AuthSingleton;

class SynthesisStageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Признак 0/1 если создатель услуги совпадает с текущим пользователем
        $isOwner = $this->chemist_id === AuthSingleton::getId() ? 1 : 0;

        return [
            'id' => $this->id,
            'name' => $this->name,
            'brief_description' => $this->brief_description,
            'status' => $this->lifecycle_status,
            'image_url' => $this->media_image_url,
            'video_url' => $this->media_video_url,
            'yield_percentage' => (float) $this->yield_percentage,
            'temperature_celsius' => (int) $this->reaction_temperature_celsius,
            'likes_count' => $this->liking_chemists_count ?? 0,
            'is_owner' => $isOwner,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
