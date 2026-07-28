<?php

namespace App\Http\Resources\Api;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FindUsLocationResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'branch_name' => $this->branch_name,
            'branch_address' => $this->branch_address,
            'sort_order' => $this->sort_order,
            'map_embed_url' => $this->map_embed_url,
        ];
    }
}
