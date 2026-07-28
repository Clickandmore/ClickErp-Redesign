<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Models\Contract;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class RecruitmentContractQuickFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @test */
    public function delayed_arrival_filter_uses_45_days_and_excludes_ticket_stage(): void
    {
        Carbon::setTestNow('2026-07-28 10:00:00');

        $query = Contract::query();
        $this->applyStatusFilter($query, 'delayed_arrival');

        $bindings = $query->getBindings();

        $this->assertContains('2026-06-13', $bindings);
        $this->assertContains(ContractStatusEnum::Taffez->value, $bindings);
        $this->assertNotContains(ContractStatusEnum::Tazker->value, $bindings);
    }

    /** @test */
    public function arrival_today_filter_uses_the_current_date(): void
    {
        Carbon::setTestNow('2026-07-28 10:00:00');

        $query = Contract::query();
        $this->applyStatusFilter($query, 'arrival_today');

        $bindings = $query->getBindings();

        $this->assertContains('2026-07-28', $bindings);
        $this->assertContains(ContractStatusEnum::Tazker->value, $bindings);
        $this->assertContains(ContractStatusEnum::Arrival->value, $bindings);
    }

    private function applyStatusFilter($query, string $status): void
    {
        $method = new ReflectionMethod(RecruitmentContractController::class, 'applyStatusFilter');
        $method->setAccessible(true);
        $method->invoke(new RecruitmentContractController(), $query, $status);
    }
}
