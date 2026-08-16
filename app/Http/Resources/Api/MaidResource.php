<?php

namespace App\Http\Resources\Api;

use App\Models\ingaz\Setting;
use Illuminate\Http\Resources\Json\JsonResource;

class MaidResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array|\Illuminate\Contracts\Support\Arrayable|\JsonSerializable
     */
    public function toArray($request)
        {
            $setting = Setting::first();
        return [
			'id' => (int)$this->id,
			'cv_code' => $this->cv_code,
			'name' => $this->name,
			'nationality_id' => (int)$this->nationality_id,
            'nationality' => NationalityResource::make($this->nationality),
			'passport_num' => (string)$this->passport_num,
			'phone' => $this->phone,
			'relative_name' => $this->relative_name,
			'relative_phone' => $this->relative_phone,
			'residence_city' => $this->residence_city,
			'passport_issue_place' => $this->passport_issue_place,
			'education' => $this->education,
			'has_driving_license' => $this->has_driving_license,
			// 'price' => (float) $this->price,
			'price' => (string)($this->resolved_price ?? 0),
			// 'salary' => (float) $this->salary,
			'salary' => (string)($this->resolved_salary ?? 0),
			'religion_id' => (int)$this->religion_id,
            'religion' => ReligionResource::make($this->religion),
			'age' => (string)$this->age,
			'experience_years' => (string)$this->experience_years,
			'cv_file' =>(string)  showFile($this->cv_file),
			'video_link' => $this->video_link,
			'image' => (string) showFile($this->image),
			'available' => (boolean)$this->available,
			'customer_service_whatsapp' => (string)@$setting->whatsapp,
			'available_at' => (string)$this->available_at,
			'is_show' => (boolean)$this->is_show,
			'job_types' => MaidJobResource::collection($this->job_types),
			'job_titles' => MaidJobTitleResource::make($this->job_titles->first()),
			'expertises' => MaidExpertiseResource::collection($this->whenLoaded('expertises')),
			'recruitment_office' => [
                'id' => $this->recruitmentOffice?->id,
                'title' => $this->recruitmentOffice?->title,
            ],
			'branch' => \App\Support\SystemModuleManager::isEnabled('branches') && $this->branch
				? ['id' => (int) $this->branch->id, 'name' => (string) $this->branch->name]
				: null,
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
