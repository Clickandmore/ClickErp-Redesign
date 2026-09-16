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

    public function test_steps_can_be_completed_independently_of_configured_order(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('المرحلة الأولى', 10);
        $second = $this->makeStep('المرحلة الثانية', 20);

        $this->assertSame($first->id, $this->service->currentStep($contract)?->id);

        $this->service->assertCurrentStep($contract, $second);
        $this->service->completeMany($contract, $contract->status, [
            ['id' => $second->id, 'completed_at' => '2026-01-15'],
        ], null);
        $this->assertSame($first->id, $this->service->currentStep($contract)?->id);

        RecruitmentContractStepCompletion::query()->create([
            'contract_id' => $contract->id,
            'stage_step_id' => $first->id,
            'completed_at' => now(),
        ]);

        $this->assertNull($this->service->currentStep($contract));
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

    public function test_reopening_a_step_preserves_other_completed_steps(): void
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
        $this->assertSame(1, RecruitmentContractStepCompletion::query()
            ->where('contract_id', $contract->id)
            ->whereNotNull('completed_at')
            ->count());
    }

    public function test_multiple_steps_keep_their_individual_dates_and_notes(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('الأولى', 10);
        $second = $this->makeStep('الثانية', 20);
        $this->service->completeMany($contract, $contract->status, [
            ['id' => $second->id, 'completed_at' => '2026-01-12', 'notes' => 'ملاحظة ثانية'],
            ['id' => $first->id, 'completed_at' => '2026-01-10', 'notes' => 'ملاحظة أولى'],
        ], null);
        $completions = RecruitmentContractStepCompletion::where('contract_id', $contract->id)->get()->keyBy('stage_step_id');
        $this->assertSame('2026-01-10', $completions[$first->id]->completed_at->toDateString());
        $this->assertSame('2026-01-12', $completions[$second->id]->completed_at->toDateString());
        $this->assertSame('ملاحظة أولى', $completions[$first->id]->notes);
        $this->assertSame('ملاحظة ثانية', $completions[$second->id]->notes);
        $this->service->assertStageComplete($contract);

        $this->service->completeMany($contract, $contract->status, [
            ['id' => $first->id, 'completed_at' => '2026-02-10', 'notes' => 'تكرار'],
        ], null);
        $this->assertSame('2026-01-10', $completions[$first->id]->fresh()->completed_at->toDateString());
        $this->assertSame('ملاحظة أولى', $completions[$first->id]->fresh()->notes);
    }

    public function test_invalid_step_rejects_the_entire_batch(): void
    {
        $contract = $this->makeContract();
        $first = $this->makeStep('الأولى', 10);
        $invalid = $this->makeStep('غير متاحة', 20);
        $invalid->update(['is_active' => false]);
        try {
            $this->service->completeMany($contract, $contract->status, [
                ['id' => $first->id, 'completed_at' => '2026-01-10'],
                ['id' => $invalid->id, 'completed_at' => '2026-01-12'],
            ], null);
            $this->fail('Invalid steps must reject the batch.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('steps', $exception->errors());
        }
        $this->assertSame(0, RecruitmentContractStepCompletion::where('contract_id', $contract->id)->count());
    }

    public function test_stale_stage_cannot_be_completed(): void
    {
        $contract = $this->makeContract();
        $step = $this->makeStep('الأولى', 10);
        $this->expectException(ValidationException::class);
        $this->service->completeMany($contract, 'old-stage', [
            ['id' => $step->id, 'completed_at' => '2026-01-10'],
        ], null);
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

    public function test_batch_routes_resolve_to_the_expected_actions(): void
    {
        foreach (['index' => ['GET', 'stageSteps'], 'complete' => ['POST', 'completeStageSteps']] as $name => [$method, $action]) {
            $url = route('RecruitmentContract.stageSteps.' . $name, ['contract' => 123]);
            $matched = app('router')->getRoutes()->match(\Illuminate\Http\Request::create($url, $method));
            $this->assertSame('RecruitmentContract.stageSteps.' . $name, $matched->getName());
            $this->assertSame($action, $matched->getActionMethod());
        }
    }
}
