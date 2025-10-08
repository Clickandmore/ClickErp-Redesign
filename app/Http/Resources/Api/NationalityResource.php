<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NationalityResource extends JsonResource
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
			'id' => (int)$this->id,
            'logo' => (string)showFile($this->logo),
            'country' => (string)$this->title,
			'nationality' => (string)$this->sub_title,
			'phone_code' => (string)$this->phone_code,
			'description' => (string)strip_tags($this->description ?? ''),
			'recruitment_cost' => $this->price,
			'rental_cost' => $this->rental_cost,
			'service_transportation_cost' => $this->service_transportation_cost,
			'monthly_salary' => $this->monthly_salary,
			'is_show' => (bool)$this->is_show,
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
