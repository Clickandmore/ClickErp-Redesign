<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class GuidelineSectionResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'files' => [
                'client_rights_and_obligations' => $this->filePayload($this->client_file_path),
                'worker_rights_and_obligations' => $this->filePayload($this->worker_file_path),
            ],
            'videos' => $this->videos->map(fn ($video) => [
                'id' => $video->id,
                'youtube_url' => $video->youtube_url,
            ])->values(),
            'nationalities' => $this->nationalityGuidelines
                ->filter(fn ($guideline) => $guideline->nationality && $guideline->versions->isNotEmpty())
                ->map(function ($guideline) {
                    $nationality = $guideline->nationality;

                    return [
                        'id' => $nationality->id,
                        'name' => [
                            'ar' => $this->nationalityName($nationality, 'ar'),
                            'en' => $this->nationalityName($nationality, 'en'),
                        ],
                        'image_url' => $nationality->logo ? showFile($nationality->logo) : null,
                        'languages' => $guideline->versions->map(fn ($version) => [
                            'id' => $version->id,
                            'language' => $version->language,
                            'files' => [
                                'client_rights_and_obligations' => $this->filePayload($version->client_file_path),
                                'worker_rights_and_obligations' => $this->filePayload($version->worker_file_path),
                            ],
                        ])->values(),
                    ];
                })
                ->values(),
        ];
    }

    private function filePayload(?string $path): ?array
    {
        return $path ? ['url' => showFile($path)] : null;
    }

    private function nationalityName($nationality, string $locale): string
    {
        $translation = $nationality->translations->firstWhere('locale', $locale);

        return trim((string) ($translation?->sub_title ?: $translation?->title));
    }
}
