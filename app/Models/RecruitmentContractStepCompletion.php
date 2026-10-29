<?php

namespace App\Models;

use App\Models\auth\Admin;
use Illuminate\Database\Eloquent\Model;

class RecruitmentContractStepCompletion extends Model
{
    protected static function booted(): void
    {
        static::updated(function (self $completion): void {
            if ($completion->wasChanged('completed_at') && $completion->completed_at) {
                $contract = Contract::find($completion->contract_id);
                $step = $contract ? app(\App\Services\ingaz\RecruitmentContractStageStepService::class)
                    ->applicableSteps($contract)->firstWhere('id', $completion->stage_step_id) : null;
                if ($contract && $step) {
                    app(\App\Services\ingaz\RecruitmentStageNotifier::class)->stepCompleted($contract, $step);
                }
            }
        });
        static::created(function (self $completion): void {
            if ($completion->completed_at) {
                $contract = Contract::find($completion->contract_id);
                $step = $contract ? app(\App\Services\ingaz\RecruitmentContractStageStepService::class)
                    ->applicableSteps($contract)->firstWhere('id', $completion->stage_step_id) : null;
                if ($contract && $step) {
                    app(\App\Services\ingaz\RecruitmentStageNotifier::class)->stepCompleted($contract, $step);
                }
            }
        });
    }

    use \App\Models\Concerns\AuditsChanges;

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
