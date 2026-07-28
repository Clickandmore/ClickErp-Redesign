<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class WhyChooseUsRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title:ar' => 'required|string|max:255',
            'title:en' => 'required|string|max:255',
            'description:ar' => 'required|string|max:3000',
            'description:en' => 'required|string|max:3000',
            'is_active' => 'required|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp|max:5120',
            'delete_image_ids' => 'nullable|array',
            'delete_image_ids.*' => 'integer|exists:why_choose_us_images,id',
            'features' => 'required|array|min:1|max:12',
            'features.*.id' => 'nullable|integer|exists:why_choose_us_features,id',
            'features.*.icon' => 'required|string|max:100',
            'features.*.sort_order' => 'required|integer|min:1|max:9999',
            'features.*.ar.title' => 'required|string|max:255',
            'features.*.ar.description' => 'required|string|max:1000',
            'features.*.en.title' => 'required|string|max:255',
            'features.*.en.description' => 'required|string|max:1000',
        ];
    }
}
