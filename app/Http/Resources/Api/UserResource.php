<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
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
			'name' => (string)$this->name,
			'phone_code' => (string)$this->phone_code,
			'phone' => (string)$this->phone,
			'email' => (string)$this->email,
			'image' => $this->image,
			'image_url' => showFile($this->image),
			'nationality_id' => $this->nationality_id ? (int) $this->nationality_id : null,
			'nationality' => $this->whenLoaded('nationality', fn () => $this->nationality ? [
				'id' => (int) $this->nationality->id,
				'title' => (string) $this->nationality->title,
			] : null),
			'national_num' => $this->national_num,
			'birthdate' => $this->birthdate,
			'marital_status' => $this->marital_status,
			'phone2' => $this->phone2,
			'address' => $this->address,
			'city' => $this->city,
			'rooms_num' => $this->rooms_num,
			'family_num' => $this->family_num,
			'children' => $this->children,
			'job' => $this->job,
			'full_name_en' => $this->full_name_en,
			'identity_type' => $this->identity_type,
			'identity_date' => $this->identity_date,
			'residence_expiry_date' => optional($this->residence_expiry_date)->format('Y-m-d'),
			'workplace' => $this->workplace,
			'po_box' => $this->po_box,
			'postal_code' => $this->postal_code,
			'is_blocked' => (boolean)$this->is_blocked,
            'token' => isset($this->token) ? $this->token : '',
			// 'remember_token' => (string)$this->remember_token,
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
