<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
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
			"id" => (int)$this->id,
			"name" => (string)$this->name,
			"branch_id" => $this->branch_id ? (int)$this->branch_id : null,
			"branch" => $this->whenLoaded('branch', function () {
				return $this->branch ? [
					"id" => (int)$this->branch->id,
					"name" => (string)$this->branch->name,
				] : null;
			}),
			"phone" => (string)$this->phone,
			"email" => (string)$this->email,
			"image" => (string)showFile($this->image),
			"sex_type" => $this->sex_type,
			"whatsapp" => (string)$this->whatsapp,
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
	}
}
