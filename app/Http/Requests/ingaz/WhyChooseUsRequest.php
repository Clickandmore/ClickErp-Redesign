<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;
use App\Models\WhyChooseUsFeature;

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
            'cta_label:ar' => 'nullable|string|max:120',
            'cta_label:en' => 'nullable|string|max:120',
            // A full URL (https://...) or an internal path (/professional-workers).
            'cta_url' => ['nullable', 'string', 'max:255', function ($attribute, $value, $fail) {
                if (filter_var($value, FILTER_VALIDATE_URL) !== false || preg_match('#^/[^\s]*$#', $value)) {
                    return;
                }

                $fail(__('why_choose_us.cta_url_invalid'));
            }],
            'is_active' => 'required|boolean',
            'images' => 'nullable|array|max:8',
            'images.*' => 'image|mimes:jpg,jpeg,png,webp,svg|max:5120',
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

            // Highlights carry a label only, so no description is required.
            'highlights' => 'nullable|array|max:8',
            'highlights.*.id' => 'nullable|integer|exists:why_choose_us_features,id',
            'highlights.*.icon' => 'required|string|max:100',
            'highlights.*.sort_order' => 'required|integer|min:1|max:9999',
            'highlights.*.ar.title' => 'required|string|max:255',
            'highlights.*.en.title' => 'required|string|max:255',
        ];
    }

    /**
     * Every row the form submits, tagged with the list it came from.
     *
     * @return array<int, array<string, mixed>>
     */
    public function featureRows(): array
    {
        $rows = [];

        foreach (array_values($this->input('features', [])) as $data) {
            $data['type'] = WhyChooseUsFeature::TYPE_FEATURE;
            $rows[] = $data;
        }

        foreach (array_values($this->input('highlights', [])) as $data) {
            $data['type'] = WhyChooseUsFeature::TYPE_HIGHLIGHT;
            $data['ar']['description'] = $data['ar']['description'] ?? null;
            $data['en']['description'] = $data['en']['description'] ?? null;
            $rows[] = $data;
        }

        return $rows;
    }
}
