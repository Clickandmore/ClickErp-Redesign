<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class WhyChooseUsResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            // The title may wrap one phrase in [[ ]] to have the site colour it.
            'title' => $this->title,
            'description' => $this->description,
            'cta_label' => $this->cta_label,
            'cta_url' => $this->cta_url,
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
            'highlights' => $this->highlights->map(fn ($highlight) => [
                'id' => $highlight->id,
                'title' => $highlight->title,
                'icon' => $highlight->icon,
                'sort_order' => $highlight->sort_order,
            ])->values(),
        ];
    }
}
