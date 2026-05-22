<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class PackageResource extends JsonResource
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
			'original_price' => (string)$this->original_price,
			'price_after_discount' => (string)$this->price_after_discount,
			'vat_amount' => (string)number_format(max(0, (float)$this->price_with_tax - (float)$this->original_price), 2, '.', ''),
			'price_with_tax' => (string)$this->price_with_tax,
			'offer_amount' => $this->offer_amount !== null ? (string)$this->offer_amount : null,
			'offer_starts_at' => $this->offer_starts_at ? (string)$this->offer_starts_at->format('Y-m-d') : null,
			'offer_ends_at' => $this->offer_ends_at ? (string)$this->offer_ends_at->format('Y-m-d') : null,
			'offer_is_active' => (bool)($this->offer_is_active ?? false),
			'offer_description' => (string)($this->offer_description ?? ''),
			'days_count' => (string)$this->days_count,
			'nationality_id' => $this->nationality_id ? (int)$this->nationality_id : null,
			'rental_type' => (string)($this->rental_type ?? 'monthly'),
			'rental_type_label' => (string)$this->rental_type_label,
			'duration_unit' => (string)($this->duration_unit ?? 'month'),
			'duration_unit_label' => (string)$this->duration_unit_label,
			'duration_count' => (int)($this->duration_count ?: 1),
			'duration_label' => (string)$this->duration_label,
			'sort_order' => (int)($this->sort_order ?? 0),
			'working_hours_per_day' => $this->working_hours_per_day ? (int)$this->working_hours_per_day : null,
			'description' => (string)($this->description ?? ''),
			// 'created_at' => (string)$this->created_at,
			// 'updated_at' => (string)$this->updated_at,
		];
    }
}
