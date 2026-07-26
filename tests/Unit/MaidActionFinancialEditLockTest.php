<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Http\Actions\ingaz\MaidExpertiseAction;
use App\Models\ingaz\Maid;
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

        $action = new MaidAction(
            Mockery::mock(Maid::class),
            Mockery::mock(MaidExpertiseAction::class),
            Mockery::mock(RentalMaidFinanceService::class),
            Mockery::mock(RentalMaidAccountingService::class),
        );

        return [$action, $maid];
    }

    private function invokeGuard(MaidAction $action, Maid $maid, array $data): void
    {
        $method = new ReflectionMethod(MaidAction::class, 'guardFinanciallyLinkedMaidIdentityChanges');
        $method->setAccessible(true);
        $method->invoke($action, $maid, $data, [3]);
    }
}
