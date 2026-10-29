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
    public static function stepSnapshot(): string
    {
        return RecruitmentContractStageStep::where('is_active', true)->orderBy('sort_order')->orderBy('id')
            ->get()->toJson(JSON_UNESCAPED_UNICODE);
    }

    public static function freezeExistingContracts(): void
    {
        // Freeze definitions before editing them, including contracts not opened by a follower yet.
        DB::table('contracts')->whereIn('type', ['recruitment', 'maid'])->whereNull('recruitment_steps_snapshot')
            ->update(['recruitment_steps_snapshot' => self::stepSnapshot()]);
    }

    public function nationalityId(Contract $contract): ?int
    {
        $id = $contract->maid?->nationality_id
            ?: $contract->required_maid_nationality_id
            ?: $contract->info?->manual_maid_nationality_id;

        return $id ? (int) $id : null;
    }

    public function applicableSteps(Contract $contract, bool $lock = false): Collection
    {
        if (!\App\Support\RecruitmentStagePolicy::allowsSteps((string) $contract->status)) {
            return collect();
        }
        $nationalityId = $this->nationalityId($contract);

        $snapshot = $contract->recruitment_steps_snapshot;
        if ($snapshot === null) {
            DB::table('contracts')->where('id', $contract->id)->whereNull('recruitment_steps_snapshot')
                ->update(['recruitment_steps_snapshot' => self::stepSnapshot()]);
            $snapshot = DB::table('contracts')->where('id', $contract->id)->value('recruitment_steps_snapshot');
            $contract->recruitment_steps_snapshot = $snapshot;
        }
        return collect(json_decode((string) $snapshot, true) ?: [])
            ->filter(fn ($step) => $step['stage'] === $contract->status
                && (empty($step['nationality_id']) || (int) $step['nationality_id'] === $nationalityId))
            ->map(fn ($step) => (new RecruitmentContractStageStep)->newFromBuilder($step))->values();
    }

    public function currentStep(Contract $contract, bool $requiredOnly = false): ?RecruitmentContractStageStep
    {
        $steps = $this->applicableSteps($contract);
        if ($requiredOnly) {
            $steps = $steps->filter(fn ($step) => $step->is_required);
        }
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
        $current = $this->currentStep($contract, true);
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
            $contract = Contract::query()->lockForUpdate()->findOrFail($contract->id);
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
