<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MusanedSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'ratios' => $this->metricsOfType('ratio'),
            'star_ratings' => $this->metricsOfType('rating'),
            'overall_performance' => $this->overall_performance,
            'profile_url' => $this->profile_url,
        ];
    }

    private function metricsOfType(string $type): array
    {
        return $this->metrics
            ->where('type', $type)
            ->where('is_visible', true)
            ->sortBy('sort_order')
            ->values()
            ->map(fn ($metric) => [
                'id' => $metric->id,
                'title' => $metric->title,
                'value' => $metric->value,
                'icon' => $metric->icon ? showFile($metric->icon) : null,
            ])
            ->all();
    }
}
