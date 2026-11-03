<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
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
			'title' => (string)$this->title,
			'description' => (string)$this->description,
			'type' => (string)$this->type,
			// Only the customer notifications list sets the read time; other callers see every item as read.
			'is_read' => !$request->attributes->has('notifications_read_at')
				|| ($request->attributes->get('notifications_read_at') && $this->created_at <= $request->attributes->get('notifications_read_at')),
			'order_id' => (int)$this->order_id,
			// طلب الساعات الذي يخصه الإشعار: رقمه الظاهر للعميل ومعرّفه لفتحه، والزيارة إن كان عن زيارة واحدة.
			'hourly_order' => $this->hourly_order_id ? ['id' => (int)$this->hourly_order_id, 'order_number' => $this->hourlyOrder?->order_number,
				'visit_id' => $this->hourly_visit_id ? (int)$this->hourly_visit_id : null, 'visit_number' => $this->hourlyVisit?->visit_number] : null,
            'order' => OrderResource::make($this->order),
			'user_id' => (int)$this->user_id,
            'user' => UserResource::make($this->user),
			 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
