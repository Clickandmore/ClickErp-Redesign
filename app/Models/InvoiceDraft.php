<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use App\Models\Concerns\BranchScoped;
use Illuminate\Database\Eloquent\Model;

class InvoiceDraft extends Model
{
    use AssignsBranch, BranchScoped, \App\Models\Concerns\AuditsChanges;

    protected $guarded = [];
    protected $casts = ['payload' => 'array', 'reviewed_at' => 'datetime'];

    public function creator()
    {
        return $this->belongsTo(\App\Models\auth\Admin::class, 'created_by');
    }

    public function branch() { return $this->belongsTo(Branch::class, 'branch_id'); }
    public function reviewer() { return $this->belongsTo(\App\Models\auth\Admin::class, 'reviewed_by'); }
    public function getContractIdAttribute() { return $this->payload['contract_id'] ?? null; }
    public function getClientIdAttribute() { return $this->payload['client_id'] ?? null; }
    public function getIngazDelegationIdAttribute() { return $this->payload['ingaz_delegation_id'] ?? null; }
    public function contract() { return $this->belongsTo(Contract::class, 'contract_id'); }
    public function client() { return $this->belongsTo(\App\Models\ingaz\User::class, 'client_id'); }
    public function delegation() { return $this->belongsTo(IngazDelegation::class, 'ingaz_delegation_id'); }
}
