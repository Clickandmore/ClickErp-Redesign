<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Resources\Json\JsonResource;

class RetuenRequestsResource extends JsonResource
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
            'id' => (int) $this->id,
            'contract_id' => (int) $this->contract_id,
            'user_id' => (int) $this->user_id,

            'status' => (string) $this->status,
            'status_text' => (string) $this->status_text,

            'notes' => $this->notes ? (string) $this->notes : null,
            'admin_notes' => $this->admin_notes ? (string) $this->admin_notes : null,

            'processed_by' => $this->processed_by ? (int) $this->processed_by : null,
            'processed_at' => $this->processed_at ? (string) $this->processed_at : null,

            'contract' => $this->relationLoaded('contract') && $this->contract ? (function () {
                $maid = $this->contract->maid;

                return [
                    'id' => (int) $this->contract->id,
                    'status' => (string) $this->contract->status,
                    'maid' => $maid ? [
                        'id' => (int) $maid->id,
                        'name' => (string) $maid->name,
                        'passport_num' => $maid->passport_num ? (string) $maid->passport_num : null,
                        'image' => showFile($maid->image),
                    ] : null,
                ];
            })() : null,

            'user' => $this->relationLoaded('user') && $this->user ? [
                'id' => (int) $this->user->id,
                'name' => (string) $this->user->name,
                'phone' => (string) ($this->user->phone_code . $this->user->phone),
                'email' => $this->user->email ? (string) $this->user->email : null,
            ] : null,

            'processed_by_admin' => $this->relationLoaded('processedBy') && $this->processedBy ? [
                'id' => (int) $this->processedBy->id,
                'name' => (string) $this->processedBy->name,
            ] : null,

            'created_at' => (string) $this->created_at,
            'updated_at' => (string) $this->updated_at,
        ];
    }
}
