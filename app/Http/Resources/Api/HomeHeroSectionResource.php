<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class HomeHeroSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        $videoType = in_array($this->video_type, ['upload', 'youtube'], true)
            ? $this->video_type
            : ($this->video_path ? 'upload' : ($this->video_url ? 'youtube' : null));
        $videoUrl = $videoType === 'upload'
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
            'video' => $videoType && $videoUrl
                ? [
                    'title' => $this->video_title,
                    'type' => $videoType,
                    'url' => $videoUrl,
                ]
                : null,
        ];
    }
}
