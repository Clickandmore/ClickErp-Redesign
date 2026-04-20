<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class SponsorshipTransferResource extends JsonResource
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
            'contract_id' => (int)$this->contract_id,
            'old_sponsor_id' => (int)$this->old_sponsor_id,
            'new_sponsor_id' => (int)$this->new_sponsor_id,

            'status' => (string)$this->status,
            'status_text' => (string)$this->status_text,

            'trial_days' => (int)$this->trial_days,
            'trial_start_date' => (string)$this->trial_start_date,

            'contract_value' => (string)$this->contract_value,
            'paid_amount' => (string)$this->paid_amount,
            'remaining_amount' => (string)$this->remaining_amount,

            'notes' => (string)$this->notes,
            'failure_reason' => (string)$this->failure_reason,

            'old_sponsor' => UserResource::make($this->oldSponsor),
            'new_sponsor' => UserResource::make($this->newSponsor),

            'approved_by' => (int)$this->approved_by,
            'approved_at' => (string)$this->approved_at,
            'trial_started_at' => (string)$this->trial_started_at,
            'transferred_at' => (string)$this->transferred_at,
            'failed_at' => (string)$this->failed_at,

            'created_at' => (string)$this->created_at,
            // 'updated_at' => (string)$this->updated_at,
        ];
    }
}
