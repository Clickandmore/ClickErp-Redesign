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
            'start_date' => $this->start_date?->toDateString(),
            'end_date' => $this->end_date?->toDateString(),
            'number_of_years' => (string) $this->number_of_years,
            // جاهزة للعرض: "2018م – 2022م" و "4 سنوات و 5 أشهر"
            'duration_months' => $this->duration_months,
            'period_label' => $this->period_label,
            'duration_label' => $this->duration_label,
            'job_title_id' => (int) $this->job_title_id,
            'job_title' => $this->jobTitle?->title,
        ];
    }
}
