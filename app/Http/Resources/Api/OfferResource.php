<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class OfferResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => (int) $this->id,
            'type' => (string) $this->type,
            'type_label' => (string) $this->type_label,
            'title' => (string) $this->title,
            'description' => (string) $this->description,
            'image' => (string) showFile($this->image),
            'price_before_discount' => $this->price_before_discount !== null ? (string) $this->price_before_discount : null,
            'price_after_discount' => $this->price_after_discount !== null ? (string) $this->price_after_discount : null,
            'discount_percentage' => $this->discount_percentage !== null ? (string) $this->discount_percentage : null,
            'url' => $this->url,
            'starts_at' => optional($this->starts_at)->toDateString(),
            'ends_at' => optional($this->ends_at)->toDateString(),
            'is_active' => (bool) $this->is_active,
            'sort_order' => (int) $this->sort_order,
            'nationalities' => NationalityResource::collection($this->whenLoaded('nationalities')),
            'maids' => MaidCardResource::collection($this->whenLoaded('maids')),
        ];
    }
}
