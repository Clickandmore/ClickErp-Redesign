<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MaidExpertiseResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => (int) $this->id,
            'country_id' => (int) $this->country_id,
            'country_name_ar' => $this->country?->name_ar,
            'country_name_en' => $this->country?->name_en,
            'work_city' => $this->work_city,
            'number_of_years' => (string) $this->number_of_years,
            'job_title_id' => (int) $this->job_title_id,
            'job_title' => $this->jobTitle?->title,
        ];
    }
}
