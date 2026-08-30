<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class MusanedSectionRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $normalizeMetrics = static function ($metrics): array {
            return collect(is_array($metrics) ? $metrics : [])
                ->values()
                ->map(function ($metric) {
                    // A malformed nested field (for example, ratios[0][title]=1)
                    // must be left for the validation rules to report, rather than
                    // causing a TypeError while normalizing its sibling values.
                    $metric = is_array($metric) ? $metric : [];

                    if (array_key_exists('title', $metric) && ! is_array($metric['title'])) {
                        $metric['title'] = [];
                    }

                    $metric['is_visible'] = filter_var(
                        $metric['is_visible'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );
                    $metric['remove_icon'] = filter_var(
                        $metric['remove_icon'] ?? false,
                        FILTER_VALIDATE_BOOLEAN
                    );

                    return $metric;
                })
                ->all();
        };

        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'ratios' => $normalizeMetrics($this->input('ratios')),
            'star_ratings' => $normalizeMetrics($this->input('star_ratings')),
        ]);
    }

    public function rules(): array
    {
        return [
            'title:ar' => 'required|string|max:255|not_in:1',
            'title:en' => 'required|string|max:255|not_in:1',
            'subtitle:ar' => 'required|string|max:2000|not_in:1',
            'subtitle:en' => 'required|string|max:2000|not_in:1',
            'overall_performance:ar' => 'required|string|max:255|not_in:1',
            'overall_performance:en' => 'required|string|max:255|not_in:1',
            'is_active' => 'required|boolean',
            'ratios' => 'present|array|max:50',
            'ratios.*.title' => 'required|array',
            'ratios.*.title.ar' => 'required|string|max:255|not_in:1',
            'ratios.*.title.en' => 'required|string|max:255|not_in:1',
            'ratios.*.id' => 'nullable|integer|exists:musaned_section_metrics,id',
            'ratios.*.value' => 'required|numeric|min:0|max:100',
            'ratios.*.icon' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'ratios.*.remove_icon' => 'required|boolean',
            'ratios.*.is_visible' => 'required|boolean',
            'star_ratings' => 'present|array|max:50',
            'star_ratings.*.title' => 'required|array',
            'star_ratings.*.title.ar' => 'required|string|max:255|not_in:1',
            'star_ratings.*.title.en' => 'required|string|max:255|not_in:1',
            'star_ratings.*.id' => 'nullable|integer|exists:musaned_section_metrics,id',
            'star_ratings.*.value' => 'required|numeric|min:0|max:5',
            'star_ratings.*.icon' => 'nullable|image|mimes:jpg,jpeg,png,webp,svg|max:5120',
            'star_ratings.*.remove_icon' => 'required|boolean',
            'star_ratings.*.is_visible' => 'required|boolean',
            'profile_url' => 'required|url|max:2048',
        ];
    }
}
