<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\TravelHistory;
use Tests\TestCase;

class RecruitmentContractArrivalExpiryTest extends TestCase
{
    public function test_contract_period_starts_from_travel_arrival_date(): void
    {
        $contract = new Contract(['contract_date' => '2020-01-01']);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'completed',
            'arrival_date' => '2024-03-10',
            'arrival_time' => '13:30:00',
        ]));
        $contract->setRelation('info', new ContractInfo([
            'contract_date' => '2020-01-01',
            'arrival_date' => '2024-02-01',
            'contract_duration' => 2,
        ]));
        $contract->setRelation('maid', new Maid([
            'arrival_date' => '2024-01-01',
        ]));

        $this->assertSame(
            '2024-03-10 13:30:00',
            $contract->resolveRecruitmentArrivalDateTime()?->format('Y-m-d H:i:s')
        );
        $this->assertSame(
            '2026-03-10',
            $contract->recruitmentContractExpiryDate()?->toDateString()
        );
    }

    public function test_contract_info_arrival_is_used_when_there_is_no_travel_arrival(): void
    {
        $contract = new Contract([
            'contract_date' => '2020-01-01',
            'status' => 'arrival',
        ]);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo([
            'arrival_date' => '2025-04-12',
            'contract_duration' => 3,
        ]));
        $contract->setRelation('maid', new Maid([
            'arrival_date' => '2024-01-01',
        ]));

        $this->assertSame(
            '2028-04-12',
            $contract->recruitmentContractExpiryDate()?->toDateString()
        );
    }

    public function test_cv_arrival_does_not_start_recruitment_contract_or_warranty(): void
    {
        $contract = new Contract([
            'contract_date' => '2020-01-01',
            'status' => 'completed',
        ]);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo([
            'arrival_date' => null,
            'contract_duration' => 2,
        ]));
        $contract->setRelation('maid', new Maid([
            'arrival_date' => '2023-07-20',
        ]));

        $this->assertNull($contract->resolveRecruitmentArrivalDateTime());
        $this->assertNull($contract->recruitmentContractExpiryDate());
        $contract->calculateWarrantyStatus();
        $this->assertNull($contract->warranty_start_date);
        $this->assertNull($contract->warranty_end_date);
    }

    public function test_contract_date_is_not_used_when_arrival_date_is_missing(): void
    {
        $contract = new Contract(['contract_date' => '2020-01-01']);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo([
            'contract_date' => '2020-01-01',
            'arrival_date' => null,
            'contract_duration' => 2,
        ]));
        $contract->setRelation('maid', new Maid([
            'arrival_date' => null,
        ]));

        $this->assertNull($contract->resolveRecruitmentArrivalDateTime());
        $this->assertNull($contract->recruitmentContractExpiryDate());
    }

    public function test_scheduled_arrival_does_not_start_the_contract_period(): void
    {
        $contract = new Contract([
            'contract_date' => '2026-07-21',
            'status' => 'new',
        ]);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'scheduled',
            'arrival_date' => '2026-06-22',
        ]));
        $contract->setRelation('info', new ContractInfo([
            'arrival_date' => '2026-06-22',
            'contract_duration' => 2,
        ]));
        $contract->setRelation('maid', new Maid([
            'arrival_date' => '2026-06-22',
        ]));

        $this->assertNull($contract->resolveRecruitmentArrivalDateTime());
        $this->assertNull($contract->recruitmentContractExpiryDate());
    }

    public function test_commission_warranty_ignores_recruitment_cv_arrival(): void
    {
        $contract = new Contract(['status' => 'back']);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo);
        $contract->setRelation('maid', new Maid(['arrival_date' => '2026-01-01']));
        $service = app(\App\Services\commissions\RecruitmentCommissionService::class);
        $method = new \ReflectionMethod($service, 'contractWithResolvedWarranty');
        $method->setAccessible(true);
        $resolved = $method->invoke($service, $contract);
        $this->assertNull($resolved->warranty_start_date);
        $this->assertNull($resolved->warranty_end_date);
    }
}
