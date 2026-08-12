<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\MaidExpertiseAction;
use App\Models\ingaz\Maid;
use App\PermKeys;
use App\Services\ingaz\RentalMaidAccountingService;
use App\Services\ingaz\RentalMaidFinanceService;
use App\Support\CvOptionsSettings;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class MaidActionFinancialEditLockTest extends TestCase
{
    protected function tearDown(): void
    {
        CvOptionsSettings::fakeMaidTransactionEditOverrideEnabled(null);
        Mockery::close();

        parent::tearDown();
    }

    public function test_financially_linked_maid_name_can_be_changed(): void
    {
        [$action, $maid] = $this->makeActionAndLockedMaid();

        $this->invokeGuard($action, $maid, ['name:en' => 'Updated Worker Name']);

        $this->assertTrue(true);
    }

    public function test_active_order_marks_maid_as_transaction_locked(): void
    {
        $orders = Mockery::mock();
        $orders->shouldReceive('whereIn')
            ->once()
            ->with('status', ['new', 'under_process', 'contracted', 'contracted_without_contract'])
            ->andReturnSelf();
        $orders->shouldReceive('exists')->once()->andReturnTrue();

        $maid = Mockery::mock(Maid::class)->makePartial();
        $maid->exists = true;
        $maid->id = 452;
        $maid->shouldReceive('orders')->once()->andReturn($orders);

        $this->assertTrue($maid->hasFinancialEditLock());
    }

    public function test_active_recruitment_contract_marks_maid_as_transaction_locked(): void
    {
        $orders = Mockery::mock();
        $orders->shouldReceive('whereIn')->once()->andReturnSelf();
        $orders->shouldReceive('exists')->once()->andReturnFalse();

        $contracts = Mockery::mock();
        $contracts->shouldReceive('whereIn')
            ->once()
            ->with('status', [
                \App\Enums\ingaz\ContractStatusEnum::PendingMaidSelection->value,
                \App\Enums\ingaz\ContractStatusEnum::New->value,
                \App\Enums\ingaz\ContractStatusEnum::Msaned->value,
                \App\Enums\ingaz\ContractStatusEnum::Tafwed->value,
                \App\Enums\ingaz\ContractStatusEnum::Taffez->value,
                \App\Enums\ingaz\ContractStatusEnum::Tazker->value,
                \App\Enums\ingaz\ContractStatusEnum::Arrival->value,
            ])
            ->andReturnSelf();
        $contracts->shouldReceive('exists')->once()->andReturnTrue();

        $maid = Mockery::mock(Maid::class)->makePartial();
        $maid->exists = true;
        $maid->id = 451;
        $maid->shouldReceive('orders')->once()->andReturn($orders);
        $maid->shouldReceive('contracts')->once()->andReturn($contracts);

        $this->assertTrue($maid->hasFinancialEditLock());
    }

    public function test_financially_linked_maid_passport_remains_locked(): void
    {
        [$action, $maid] = $this->makeActionAndLockedMaid();

        try {
            $this->invokeGuard($action, $maid, ['passport_num' => 'NEW-PASSPORT']);
            $this->fail('A financially linked maid passport change should be rejected.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('passport_num', $exception->errors());
        }
    }

    public function test_admin_override_allows_financially_linked_maid_identity_changes(): void
    {
        $action = $this->makeAction();
        $maid = Mockery::mock(Maid::class)->makePartial();

        $this->invokeGuard($action, $maid, ['passport_num' => 'NEW-PASSPORT'], true);

        $this->assertTrue(true);
    }

    public function test_override_is_not_registered_for_the_permissions_screen(): void
    {
        $permission = collect(config('access_control_permission_registry.permissions'))
            ->firstWhere('name', PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE);

        $this->assertNull($permission);
    }

    public function test_disabled_cv_option_keeps_linked_maid_edits_locked(): void
    {
        CvOptionsSettings::fakeMaidTransactionEditOverrideEnabled(false);

        $this->assertFalse($this->invokeCanOverride($this->makeAction()));
    }

    public function test_enabled_cv_option_unlocks_linked_maid_edits(): void
    {
        CvOptionsSettings::fakeMaidTransactionEditOverrideEnabled(true);

        $this->assertTrue($this->invokeCanOverride($this->makeAction()));
    }

    private function makeActionAndLockedMaid(): array
    {
        $maid = Mockery::mock(Maid::class)->makePartial();
        $maid->name = 'Original Worker Name';
        $maid->passport_num = 'OLD-PASSPORT';
        $maid->nationality_id = 1;
        $maid->office_id = 2;
        $maid->request_type = Maid::REQUEST_TYPE_CUSTOM;
        $maid->shouldReceive('hasFinancialEditLock')->once()->andReturnTrue();
        $maid->shouldReceive('hasExternalOfficeDispatchEditLock')->once()->andReturnFalse();

        $jobTypesRelation = Mockery::mock();
        $jobTypesRelation->shouldReceive('pluck')
            ->once()
            ->with('job_types.id')
            ->andReturn(new Collection([3]));
        $maid->shouldReceive('jobTypes')->once()->andReturn($jobTypesRelation);

        $action = $this->makeAction();

        return [$action, $maid];
    }

    private function makeAction(): MaidAction
    {
        return new MaidAction(
            Mockery::mock(Maid::class),
            Mockery::mock(MaidExpertiseAction::class),
            Mockery::mock(RentalMaidFinanceService::class),
            Mockery::mock(RentalMaidAccountingService::class),
        );
    }

    private function invokeGuard(MaidAction $action, Maid $maid, array $data, bool $canOverride = false): void
    {
        $method = new ReflectionMethod(MaidAction::class, 'guardFinanciallyLinkedMaidIdentityChanges');
        $method->setAccessible(true);
        $method->invoke($action, $maid, $data, [3], $canOverride);
    }

    private function invokeCanOverride(MaidAction $action): bool
    {
        $method = new ReflectionMethod(MaidAction::class, 'canOverrideMaidTransactionEditLock');
        $method->setAccessible(true);

        return $method->invoke($action);
    }
}
