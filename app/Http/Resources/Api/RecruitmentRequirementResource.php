<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class RecruitmentRequirementResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'icon' => $this->icon,
            'title' => $this->title,
            'sort_order' => $this->sort_order,
            'points' => RequirementPointResource::collection(
                $this->whenLoaded('items')
            ),
        ];
    }
}
