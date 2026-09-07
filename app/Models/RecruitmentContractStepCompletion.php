<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class RecruitmentContractStepCompletion extends Model
{
    use \App\Models\Concerns\AssignsBranch, \App\Models\Concerns\BranchScoped;
    protected $guarded = [];

    protected $casts = [
        'completed_at' => 'datetime',
        'completed_by_id' => 'integer',
    ];

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }

    public function step()
    {
        return $this->belongsTo(RecruitmentContractStageStep::class, 'stage_step_id')->withTrashed();
    }

    public function completedBy()
    {
        return $this->belongsTo(Admin::class, 'completed_by_id');
    }
}
