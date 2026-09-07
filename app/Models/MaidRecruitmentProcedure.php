<?php

namespace App\Models;

use App\Models\Concerns\AssignsBranch;
use Illuminate\Database\Eloquent\Model;
use App\Models\ingaz\Maid;
use App\Models\Contract;

use App\Models\Concerns\BranchScoped;

class MaidRecruitmentProcedure extends Model
{
    use \App\Models\Concerns\AuditsChanges;

    use AssignsBranch, BranchScoped;

    protected $fillable = [
        'maid_id',
        'contract_id',
        'branch_id',
        'applied_at',
        'medical_status',
        'medical_date',
        'tesda_status',
        'tesda_date',
        'owwa_status',
        'owwa_date',
        'oec_payment_status',
        'oec_payment_date',
        'oec_release_status',
        'oec_release_date',
        'embassy_stamping_status',
        'embassy_stamping_date',
        'poea_stamping_status',
        'poea_stamping_date',
        'ticket_payment_status',
        'ticket_payment_date',
        'ticket_purchase_status',
        'ticket_purchase_date',
        'remarks',
        'current_stage',
    ];

    protected $casts = [
        'applied_at' => 'date',
        'medical_date' => 'date',
        'tesda_date' => 'date',
        'owwa_date' => 'date',
        'oec_payment_date' => 'date',
        'oec_release_date' => 'date',
        'embassy_stamping_date' => 'date',
        'poea_stamping_date' => 'date',
        'ticket_payment_date' => 'date',
        'ticket_purchase_date' => 'date',
        'branch_id' => 'integer',
    ];

    public function maid()
    {
        return $this->belongsTo(Maid::class, 'maid_id');
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    protected function resolveBranchIdForCreate(): ?int
    {
        return $this->resolveBranchIdFromContract($this->contract_id)
            ?? $this->resolveBranchIdFromMaid($this->maid_id)
            ?? $this->resolveBranchIdFromCurrentAdmin();
    }
}
