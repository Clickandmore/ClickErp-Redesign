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
			'nationality_id' => (int)$this->nationality_id,
            'nationality' => NationalityResource::make($this->nationality),
			'passport_num' => (string)$this->passport_num,
			'price' => (string)$this->price,
			'salary' => (string)$this->salary,
			'religion_id' => (int)$this->religion_id,
            'religion' => ReligionResource::make($this->religion),
			'age' => (string)$this->age,
			'experience_years' => (string)$this->experience_years,
			'cv_file' =>(string)  showFile($this->cv_file),
			'image' => (string) showFile($this->image),
			'available' => (boolean)$this->available,
			'customer_service_whatsapp' => (string)@$setting->whatsapp,
			'available_at' => (string)$this->available_at,
			'is_show' => (boolean)$this->is_show,
			'jobTypes' => MaidJobResource::collection($this->job_types),
			'jobTitles' => MaidJobTitleResource::make($this->job_titles->first()),
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
