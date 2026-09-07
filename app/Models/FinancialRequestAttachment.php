<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Model;

use App\Models\Concerns\BranchScoped;

class FinancialRequestAttachment extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, BranchScoped;

    protected $guarded = [];

    protected $casts = [
        'branch_id' => 'integer',
    ];

    public function financialRequest()
    {
        return $this->belongsTo(FinancialRequest::class);
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromFinancialRequest($this->financial_request_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
