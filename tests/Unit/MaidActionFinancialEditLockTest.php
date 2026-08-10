<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\MaidExpertiseAction;
use App\Models\ingaz\Maid;
use App\PermKeys;
use App\Services\ingaz\RentalMaidAccountingService;
use App\Services\ingaz\RentalMaidFinanceService;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;
use Mockery;
use ReflectionMethod;
use Tests\TestCase;

class MaidActionFinancialEditLockTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_financially_linked_maid_name_can_be_changed(): void
    {
        [$action, $maid] = $this->makeActionAndLockedMaid();

        $this->invokeGuard($action, $maid, ['name:en' => 'Updated Worker Name']);

        $this->assertTrue(true);
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

    public function test_override_permission_is_registered_for_the_permissions_screen(): void
    {
        $permission = collect(config('access_control_permission_registry.permissions'))
            ->firstWhere('name', PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE);

        $this->assertNotNull($permission);
        $this->assertSame('تعديل السيرة المرتبطة بمعاملات', $permission['display_name']);
    }

    public function test_maid_control_permission_does_not_replace_the_dedicated_override_permission(): void
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('can')
            ->once()
            ->with(PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE)
            ->andReturnFalse();
        $this->mockAdminGuardUser($admin);

        $this->assertFalse($this->invokeCanOverride($this->makeAction()));
    }

    public function test_dedicated_override_permission_unlocks_linked_maid_edits(): void
    {
        $admin = Mockery::mock();
        $admin->shouldReceive('can')
            ->once()
            ->with(PermKeys::MAIDS_TRANSACTION_EDIT_OVERRIDE)
            ->andReturnTrue();
        $this->mockAdminGuardUser($admin);

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

    private function mockAdminGuardUser($admin): void
    {
        $guard = Mockery::mock();
        $guard->shouldReceive('user')->once()->andReturn($admin);
        $auth = Mockery::mock();
        $auth->shouldReceive('guard')->once()->with('admin')->andReturn($guard);
        $this->app->instance('auth', $auth);
    }

    private function invokeCanOverride(MaidAction $action): bool
    {
        $method = new ReflectionMethod(MaidAction::class, 'canOverrideMaidTransactionEditLock');
        $method->setAccessible(true);

        return $method->invoke($action);
    }
}
