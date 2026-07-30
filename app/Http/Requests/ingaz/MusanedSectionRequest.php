<?php

namespace App\Http\Requests\ingaz;

use App\Http\Requests\MainRequest;

class MusanedSectionRequest extends MainRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
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
            'allowed_period_commitment_title:ar' => 'required|string|max:255|not_in:1',
            'allowed_period_commitment_title:en' => 'required|string|max:255|not_in:1',
            'workforce_arrival_title:ar' => 'required|string|max:255|not_in:1',
            'workforce_arrival_title:en' => 'required|string|max:255|not_in:1',
            'complaint_handling_title:ar' => 'required|string|max:255|not_in:1',
            'complaint_handling_title:en' => 'required|string|max:255|not_in:1',
            'arrival_duration_commitment_title:ar' => 'required|string|max:255|not_in:1',
            'arrival_duration_commitment_title:en' => 'required|string|max:255|not_in:1',
            'customer_recommendation_title:ar' => 'required|string|max:255|not_in:1',
            'customer_recommendation_title:en' => 'required|string|max:255|not_in:1',
            'inquiry_response_title:ar' => 'required|string|max:255|not_in:1',
            'inquiry_response_title:en' => 'required|string|max:255|not_in:1',
            'recruitment_duration_title:ar' => 'required|string|max:255|not_in:1',
            'recruitment_duration_title:en' => 'required|string|max:255|not_in:1',
            'complaint_response_title:ar' => 'required|string|max:255|not_in:1',
            'complaint_response_title:en' => 'required|string|max:255|not_in:1',
            'is_active' => 'required|boolean',
            'allowed_period_commitment_rate' => 'required|numeric|min:0|max:100',
            'workforce_arrival_rate' => 'required|numeric|min:0|max:100',
            'complaint_handling_rate' => 'required|numeric|min:0|max:100',
            'arrival_duration_commitment_rate' => 'required|numeric|min:0|max:100',
            'customer_recommendation_rating' => 'required|numeric|min:0|max:5',
            'inquiry_response_rating' => 'required|numeric|min:0|max:5',
            'recruitment_duration_rating' => 'required|numeric|min:0|max:5',
            'complaint_response_rating' => 'required|numeric|min:0|max:5',
            'profile_url' => 'required|url|max:2048',
        ];
    }
}
