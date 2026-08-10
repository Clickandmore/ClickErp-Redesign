<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use Illuminate\Validation\Rule;

class HomeHeroSectionRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'video_type' => $this->input('video_type') ?: null,
        ]);
    }

    public function rules(): array
    {
        return [
            'title:ar' => 'required|string|max:255',
            'title:en' => 'required|string|max:255',
            'subtitle:ar' => 'required|string|max:2000',
            'subtitle:en' => 'required|string|max:2000',
            'video_title:ar' => 'nullable|string|max:255',
            'video_title:en' => 'nullable|string|max:255',
            'is_active' => 'required|boolean',
            'images' => 'nullable|array|max:12',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,svg|max:10240',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer|exists:home_hero_images,id',
            'video_type' => ['nullable', Rule::in(['upload', 'youtube'])],
            'video_file' => 'nullable|file|mimes:mp4,webm,mov|max:102400',
            'video_url' => 'nullable|required_if:video_type,youtube|url|max:2048',
        ];
    }
}
