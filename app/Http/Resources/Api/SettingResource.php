<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SettingResource extends JsonResource
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
			'whatsapp' => (string)$this->whatsapp,
            'privacy' => html_entity_decode(strip_tags($this->privacy)),
            'terms_conditions' => html_entity_decode(strip_tags($this->terms_conditions)),
            'about_us_app' => html_entity_decode(strip_tags($this->about_us_app)),
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
