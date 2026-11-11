<?php

namespace App\Models\Hourly;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class HourlyCashRemittance extends Model
{
    use AssignsBranch, BranchScoped;

    protected $guarded = [];

    public function collection()
    {
        return $this->belongsTo(HourlyCashCollection::class, 'collection_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromTable('hourly_cash_collections', $this->collection_id ?? null)
            ?: $this->resolveBranchIdFromCurrentAdmin()
            ?: $this->resolveMainBranchId();
    }
}
