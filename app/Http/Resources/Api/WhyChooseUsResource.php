<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class WhyChooseUsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
                'sort_order' => $image->sort_order,
            ])->values(),
            'sections' => $this->features->map(fn ($feature) => [
                'id' => $feature->id,
                'title' => $feature->title,
                'description' => $feature->description,
                'icon' => $feature->icon,
                'sort_order' => $feature->sort_order,
            ])->values(),
        ];
    }
}
