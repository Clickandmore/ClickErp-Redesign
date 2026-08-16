<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class MaidCardResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'name' => (string) $this->name,
            'age' => (string) $this->age,
            'passport_num' => (string) $this->passport_num,
            'phone' => $this->phone,
            'relative_name' => $this->relative_name,
            'relative_phone' => $this->relative_phone,
            'residence_city' => $this->residence_city,
            'passport_issue_place' => $this->passport_issue_place,
            'education' => $this->education,
            'has_driving_license' => $this->has_driving_license,

            'image' => (string) showFile($this->image),
            'cv_file' => (string) showFile($this->cv_file),
            'cv_preview' => (string) $this->resource->cvPreviewUrl(),
            'video' => (string) showFile($this->video),
            'video_link' => (string) $this->video_link,

            'cv_code' => (string) $this->cv_code,

            'experience_status' => (string) $this->experience_status,
            'experience_years' => (string) $this->experience_years,

            'salary' => (string) ($this->resolved_salary ?? $this->salary ?? 0),
            'price' => (string) ($this->resolved_price ?? $this->price ?? 0),

            'offer_price' => $this->offer_price,
            'offer_description' => $this->offer_description,
            'offer_starts_at' => $this->offer_starts_at,
            'offer_expires_at' => $this->offer_expires_at,

            'duration' => (string) $this->duration,

            'nationality' => [
                'id' => (int) $this->nationality_id,
                'sub_title' => (string) optional($this->nationality)->sub_title,
            ],
            'religion' => [
                'id' => (int) $this->religion_id,
                'title' => (string) optional($this->religion)->title,
            ],

            'job_types' => $this->jobTypes->map(fn($jt) => [
                'id' => (int) $jt->id,
                'title' => (string) $jt->title,
            ]),

            'job_titles' => $this->jobTitles->map(fn($jt) => [
                'id' => (int) $jt->id,
                'title' => (string) $jt->title,
            ]),

            'expertises' => MaidExpertiseResource::collection($this->whenLoaded('expertises')),

            'recruitment_office' => $this->recruitmentOffice
                ? [
                    'id' => (int) $this->recruitmentOffice->id,
                    'title' => (string) $this->recruitmentOffice->title,
                ]
                : null,
            'branch' => \App\Support\SystemModuleManager::isEnabled('branches') && $this->branch
                ? [
                    'id' => (int) $this->branch->id,
                    'name' => (string) $this->branch->name,
                ]
                : null,
        ];
    }
}
