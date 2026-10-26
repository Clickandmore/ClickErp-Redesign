<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractStatusLog;
use App\Models\TravelHistory;
use Tests\TestCase;

class RecruitmentContractLateFinanceEligibilityTest extends TestCase
{
    /** @dataProvider eligibilityCases */
    public function test_eligibility_matches_completed_contract_classification($type, $status, $previous, $travelStatus, $expected): void
    {
        $contract = new Contract(['type' => $type, 'status' => $status]);
        $contract->setRelation('contract_status_logs', collect($previous === null ? [] : [
            new ContractStatusLog(['id' => 1, 'status' => 'shelter', 'previous_status' => $previous]),
        ]));
        $contract->setRelation('travelHistory', $travelStatus ? new TravelHistory(['status' => $travelStatus]) : null);

        $this->assertSame($expected, $contract->isLateFinanceEligible());
    }

    public static function eligibilityCases(): array
    {
        return [
            ['recruitment', 'completed', null, null, true],
            ['maid', 'completed', null, null, true],
            ['recruitment', 'arrival', null, null, true],
            ['recruitment', 'refunded', null, null, true],
            ['recruitment', 'shelter', 'completed', null, true],
            ['recruitment', 'shelter', 'new', null, false],
            ['recruitment', 'shelter', null, null, true],
            ['recruitment', 'tazker', null, 'completed', true],
            ['recruitment', 'new', null, null, false],
            ['rent', 'completed', null, null, false],
        ];
    }
}
