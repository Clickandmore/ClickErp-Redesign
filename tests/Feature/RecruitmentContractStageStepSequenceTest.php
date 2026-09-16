<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\RecruitmentContractStageStep;
use App\Models\RecruitmentContractStepCompletion;
use App\Services\ingaz\RecruitmentContractStageStepService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentContractStageStepSequenceTest extends TestCase
{
    use DatabaseTransactions;

    private RecruitmentContractStageStepService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(RecruitmentContractStageStepService::class);
    }

    private function makeContract(): Contract
    {
        return Contract::query()->create([
            'status' => ContractStatusEnum::Msaned->value,
            'type' => 'recruitment',
        ]);
    }

    private function makeStep(string $name, int $order): RecruitmentContractStageStep
    {
        return RecruitmentContractStageStep::query()->create([
            'stage' => ContractStatusEnum::Msaned->value,
            'name_ar' => $name,
            'sort_order' => $order,
            'is_active' => true,
        ]);
    }

    public function test_steps_are_enforced_in_configured_order(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('المرحلة الأولى', 10);
        $second = $this->makeStep('المرحلة الثانية', 20);

        $this->assertSame($first->id, $this->service->currentStep($contract)?->id);

        try {
            $this->service->assertCurrentStep($contract, $second);
            $this->fail('The second step must not be completable before the first one.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('المرحلة الأولى', $exception->errors()['stage_step'][0]);
        }

        RecruitmentContractStepCompletion::query()->create([
            'contract_id' => $contract->id,
            'stage_step_id' => $first->id,
            'completed_at' => now(),
        ]);

        $this->assertSame($second->id, $this->service->currentStep($contract)?->id);
        $this->service->assertCurrentStep($contract, $second);
    }

    public function test_contract_cannot_leave_stage_until_every_step_is_complete(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('المرحلة الأولى', 10);
        $second = $this->makeStep('المرحلة الثانية', 20);

        RecruitmentContractStepCompletion::query()->create([
            'contract_id' => $contract->id,
            'stage_step_id' => $first->id,
            'completed_at' => now(),
        ]);

        try {
            $this->service->assertStageComplete($contract);
            $this->fail('The contract must remain in Msaned while the second step is pending.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('المرحلة الثانية', $exception->errors()['stage_steps'][0]);
        }

        RecruitmentContractStepCompletion::query()->create([
            'contract_id' => $contract->id,
            'stage_step_id' => $second->id,
            'completed_at' => now(),
        ]);

        $this->service->assertStageComplete($contract);
        $this->assertNull($this->service->currentStep($contract));
    }

    public function test_reopening_a_step_reopens_every_later_step(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('المرحلة الأولى', 10);
        $second = $this->makeStep('المرحلة الثانية', 20);

        foreach ([$first, $second] as $step) {
            RecruitmentContractStepCompletion::query()->create([
                'contract_id' => $contract->id,
                'stage_step_id' => $step->id,
                'completed_at' => now(),
            ]);
        }

        $this->service->reopenFrom($contract, $first);

        $this->assertSame($first->id, $this->service->currentStep($contract)?->id);
        $this->assertSame(0, RecruitmentContractStepCompletion::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('completed_at')
            ->count());
    }

    public function test_saved_parent_stage_and_active_state_are_rendered_as_selected(): void
    {
        $html = view('admin.ingaz.recruitment_contract_stage_labels._step_row', [
            'index' => 0,
            'step' => [
                'id' => 1,
                'stage' => ContractStatusEnum::Msaned->value,
                'name_ar' => 'مرحلة أولى',
                'name_en' => 'Stage one',
                'nationality_id' => null,
                'sort_order' => 10,
                'is_active' => 1,
            ],
            'labels' => \App\Support\RecruitmentContractStageLabels::defaults(),
            'nationalities' => collect(),
        ])->render();

        $this->assertMatchesRegularExpression('/value="msaned"\s+selected/', $html);
        $this->assertMatchesRegularExpression('/type="checkbox"[^>]+checked/s', $html);
        $this->assertStringNotContainsString('@selected', $html);
        $this->assertStringNotContainsString('@checked', $html);
    }
}
