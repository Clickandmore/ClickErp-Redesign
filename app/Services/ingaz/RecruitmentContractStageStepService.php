<?php

namespace App\Services\ingaz;

use App\Models\Contract;
use App\Models\RecruitmentContractStageStep;
use App\Models\RecruitmentContractStepCompletion;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecruitmentContractStageStepService
{
    public function nationalityId(Contract $contract): ?int
    {
        $id = $contract->maid?->nationality_id
            ?: $contract->required_maid_nationality_id
            ?: $contract->info?->manual_maid_nationality_id;

        return $id ? (int) $id : null;
    }

    public function applicableSteps(Contract $contract, bool $lock = false): Collection
    {
        $nationalityId = $this->nationalityId($contract);

        return RecruitmentContractStageStep::query()
            ->where('is_active', true)
            ->where('stage', $contract->status)
            ->where(function ($query) use ($nationalityId) {
                $query->whereNull('nationality_id');
                if ($nationalityId) {
                    $query->orWhere('nationality_id', $nationalityId);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('id')
            ->when($lock, fn ($query) => $query->lockForUpdate())
            ->get();
    }

    public function currentStep(Contract $contract): ?RecruitmentContractStageStep
    {
        $steps = $this->applicableSteps($contract);
        if ($steps->isEmpty()) {
            return null;
        }

        $completedIds = RecruitmentContractStepCompletion::query()
            ->where('contract_id', $contract->id)
            ->whereIn('stage_step_id', $steps->pluck('id'))
            ->whereNotNull('completed_at')
            ->pluck('stage_step_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        return $steps->first(fn ($step) => !in_array((int) $step->id, $completedIds, true));
    }

    public function assertCurrentStep(Contract $contract, RecruitmentContractStageStep $step): void
    {
        $current = $this->currentStep($contract);

        if (!$current || (int) $current->id !== (int) $step->id) {
            $message = $current
                ? 'يجب إنجاز الخطوة الحالية أولاً: ' . $current->displayName('ar')
                : 'هذه الخطوة ليست الخطوة الحالية للعقد.';

            throw ValidationException::withMessages(['stage_step' => $message]);
        }
    }

    public function assertStageComplete(Contract $contract): void
    {
        $current = $this->currentStep($contract);
        if ($current) {
            throw ValidationException::withMessages([
                'stage_steps' => 'لا يمكن الانتقال من مرحلة '
                    . (\App\Support\RecruitmentContractStageLabels::label($contract->status) ?: $contract->status)
                    . ' قبل إنجاز الخطوة الحالية: ' . $current->displayName('ar'),
            ]);
        }
    }

    public function reopenFrom(Contract $contract, RecruitmentContractStageStep $step): void
    {
        DB::transaction(function () use ($contract, $step) {
            $steps = $this->applicableSteps($contract, true);
            $position = $steps->search(fn ($candidate) => (int) $candidate->id === (int) $step->id);

            if ($position === false) {
                throw ValidationException::withMessages([
                    'stage_step' => 'هذه الخطوة لا تنطبق على مرحلة العقد أو جنسية العاملة الحالية.',
                ]);
            }

            RecruitmentContractStepCompletion::query()
                ->where('contract_id', $contract->id)
                ->whereIn('stage_step_id', $steps->slice($position)->pluck('id'))
                ->update([
                    'completed_at' => null,
                    'completed_by_id' => null,
                ]);
        });
    }
}
