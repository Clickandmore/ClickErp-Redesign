<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeHeroSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        $videoUrl = $this->video_type === 'upload'
            ? ($this->video_path ? showFile($this->video_path) : null)
            : $this->video_url;

        return [
            'id' => $this->id,
            'title' => $this->title,
            'subtitle' => $this->subtitle,
            'images' => $this->images->map(fn ($image) => [
                'id' => $image->id,
                'url' => $image->url,
            ])->values(),
            'video' => $this->video_type && $videoUrl
                ? [
                    'title' => $this->video_title,
                    'type' => $this->video_type,
                    'url' => $videoUrl,
                ]
                : null,
        ];
    }
}
