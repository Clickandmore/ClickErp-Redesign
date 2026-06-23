<?php

namespace App\Models\ingaz;

use App\Models\auth\Admin;
use App\Models\Concerns\AssignsBranch;
use App\Models\Contract;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ContractNote extends Model
{
    use AssignsBranch, HasFactory;

    protected $guarded = [];
    protected $fillable = ['contract_id', 'branch_id', 'admin_id', 'note'];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function contract(){
        return $this->belongsTo(Contract::class);
    }
    public function admin(){
        return $this->belongsTo(Admin::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromAdmin($this->admin_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
