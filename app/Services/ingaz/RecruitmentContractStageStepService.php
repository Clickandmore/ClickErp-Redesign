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
        if (!$this->applicableSteps($contract)->contains('id', $step->id)) {
            throw ValidationException::withMessages(['stage_step' => 'هذه الخطوة لا تنطبق على مرحلة العقد أو جنسية العاملة الحالية.']);
        }
    }

    public function completeMany(Contract $contract, string $stage, array $entries, ?int $adminId): void
    {
        DB::transaction(function () use ($contract, $stage, $entries, $adminId) {
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
            if ($contract->status !== $stage) {
                throw ValidationException::withMessages(['stage' => 'تغيرت مرحلة العقد. يرجى إعادة فتح نافذة المراحل.']);
            }
            $steps = $this->applicableSteps($contract, true)->keyBy('id');
            foreach ($entries as $entry) {
                if (!$steps->has($entry['id'])) {
                    throw ValidationException::withMessages(['steps' => 'إحدى المراحل المحددة لا تنطبق على العقد الحالي.']);
                }
            }
            foreach ($entries as $entry) {
                $completion = RecruitmentContractStepCompletion::firstOrNew([
                    'contract_id' => $contract->id,
                    'stage_step_id' => $entry['id'],
                ]);
                // Repeated submissions must not overwrite an already recorded completion.
                if ($completion->completed_at) {
                    continue;
                }
                $completion->completed_at = $entry['completed_at'];
                $completion->completed_by_id = $adminId;
                $completion->notes = trim((string) ($entry['notes'] ?? '')) ?: null;
                $completion->save();
            }
        });
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
                ->where('stage_step_id', $step->id)
                ->update([
                    'completed_at' => null,
                    'completed_by_id' => null,
                ]);
        });
    }
}
