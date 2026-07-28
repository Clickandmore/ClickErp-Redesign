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
            'ratios' => [
                'allowed_period_commitment' => [
                    'title' => $this->allowed_period_commitment_title,
                    'value' => $this->allowed_period_commitment_rate,
                ],
                'workforce_arrival' => [
                    'title' => $this->workforce_arrival_title,
                    'value' => $this->workforce_arrival_rate,
                ],
                'complaint_handling' => [
                    'title' => $this->complaint_handling_title,
                    'value' => $this->complaint_handling_rate,
                ],
                'arrival_duration_commitment' => [
                    'title' => $this->arrival_duration_commitment_title,
                    'value' => $this->arrival_duration_commitment_rate,
                ],
            ],
            'star_ratings' => [
                'customer_recommendation' => [
                    'title' => $this->customer_recommendation_title,
                    'value' => $this->customer_recommendation_rating,
                ],
                'inquiry_response' => [
                    'title' => $this->inquiry_response_title,
                    'value' => $this->inquiry_response_rating,
                ],
                'recruitment_duration' => [
                    'title' => $this->recruitment_duration_title,
                    'value' => $this->recruitment_duration_rating,
                ],
                'complaint_response' => [
                    'title' => $this->complaint_response_title,
                    'value' => $this->complaint_response_rating,
                ],
            ],
            'overall_performance' => $this->overall_performance,
            'profile_url' => $this->profile_url,
        ];
    }
}
