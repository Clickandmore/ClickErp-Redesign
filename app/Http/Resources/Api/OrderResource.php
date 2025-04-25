<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
			'payment_method' => (string)$this->payment_method,
			'maid_id' => (int)$this->maid_id,
			'maid' => MaidResource::make($this->maid),
			'user_id' => (int)$this->user_id,
			'user' => UserResource::make($this->user),
			'order_code' => (string)$this->order_code,
			'job_type_id' => (int)$this->job_type_id,
			'job_type' => JobTypeResource::make($this->jobType),
			'status' => (string)$this->status,
            'order_details ' => OrderDetailResource::make($this->details),
			 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
