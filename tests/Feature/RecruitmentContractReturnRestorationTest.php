<?php

namespace Tests\Feature;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\auth\Admin;
use App\Models\ingaz\Invoice;
use App\Services\commissions\CommissionOperationService;
use App\Services\ingaz\RecruitmentContractReturnRestorationService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RecruitmentContractReturnRestorationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_it_restores_a_returned_contract_to_its_previous_logged_status(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'marketer_id' => $marketer->id,
        ]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::New->value,
            'date' => now(),
        ]);

        $contract->update(['status' => ContractStatusEnum::Back->value]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Back->value,
            'date' => now(),
        ]);

        $restored = app(RecruitmentContractReturnRestorationService::class)
            ->restore($contract->fresh(), 1);

        $this->assertSame(ContractStatusEnum::New->value, $restored->status);
        $this->assertDatabaseHas('contract_status_logs', [
            'contract_id' => $contract->id,
            'status' => ContractStatusEnum::New->value,
        ]);
        $this->assertDatabaseMissing('commission_batch_items', [
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
        ]);
    }

    public function test_it_refuses_restoration_when_an_invoice_is_linked(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::New->value,
            'type' => 'recruitment',
            'marketer_id' => $marketer->id,
        ]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::New->value,
            'date' => now(),
        ]);
        $contract->update(['status' => ContractStatusEnum::Back->value]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Back->value,
            'date' => now(),
        ]);
        Invoice::query()->create(['contract_id' => $contract->id]);

        try {
            app(RecruitmentContractReturnRestorationService::class)
                ->restore($contract->fresh(), 1);
            $this->fail('Restoration should have been blocked by the linked invoice.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('فاتورة', $exception->errors()['contract'][0]);
        }

        $this->assertSame(ContractStatusEnum::Back->value, $contract->fresh()->status);
        $this->assertDatabaseHas('commission_batch_items', [
            'source_type' => CommissionOperationService::SOURCE_RECRUITMENT_CONTRACT,
            'source_id' => $contract->id,
        ]);
    }

    public function test_it_can_restore_a_contract_that_was_returned_from_completed_status(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Completed->value,
            'type' => 'recruitment',
            'marketer_id' => $marketer->id,
        ]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Completed->value,
            'date' => now(),
        ]);
        $contract->update(['status' => ContractStatusEnum::Back->value]);
        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Back->value,
            'date' => now(),
        ]);

        $restored = app(RecruitmentContractReturnRestorationService::class)
            ->restore($contract->fresh(), 1);

        $this->assertSame(ContractStatusEnum::Completed->value, $restored->status);
    }

    public function test_it_can_restore_with_a_selected_previous_status_when_logs_are_missing(): void
    {
        $marketer = Admin::factory()->create();
        $contract = Contract::query()->create([
            'status' => ContractStatusEnum::Back->value,
            'type' => 'recruitment',
            'marketer_id' => $marketer->id,
        ]);

        $restored = app(RecruitmentContractReturnRestorationService::class)
            ->restore($contract->fresh(), 1, ContractStatusEnum::Arrival->value);

        $this->assertSame(ContractStatusEnum::Arrival->value, $restored->status);
    }
}
