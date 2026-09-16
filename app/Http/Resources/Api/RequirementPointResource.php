<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class RequirementPointResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'icon' => $this->icon,
            'text' => $this->text,
            'description' => $this->description,
            'sort_order' => $this->sort_order,
        ];
    }
}
