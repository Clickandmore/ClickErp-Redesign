<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Models\Contract;
use App\Models\ContractExternalOfficeDispatch;
use Carbon\Carbon;
use Tests\TestCase;

class ArabiaCompletedDispatchStateTest extends TestCase
{
    public function test_completed_arabia_contract_accepts_persisted_legacy_dispatch_without_usd_cost(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Completed->value,
            'arabia_order_number' => 1234,
        ]);
        $contract->setRelation('externalOfficeDispatch', new ContractExternalOfficeDispatch([
            'dispatch_date' => Carbon::parse('2026-01-15'),
            'arrival_station' => 'الرياض',
            'office_contract_cost_usd' => null,
        ]));

        $state = $contract->getExternalOfficeDispatchState();

        $this->assertSame('completed', $state['code']);
        $this->assertTrue($state['is_completed']);
    }

    public function test_non_arabia_contract_still_requires_usd_cost(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Completed->value,
        ]);
        $contract->setRelation('externalOfficeDispatch', new ContractExternalOfficeDispatch([
            'dispatch_date' => Carbon::parse('2026-01-15'),
            'arrival_station' => 'الرياض',
            'office_contract_cost_usd' => null,
        ]));

        $state = $contract->getExternalOfficeDispatchState();

        $this->assertSame('incomplete', $state['code']);
        $this->assertFalse($state['is_completed']);
    }
}
