<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ShelterContractController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use ReflectionMethod;
use Tests\TestCase;

class DeportationProcedureDetailsTest extends TestCase
{
    public function test_it_uses_the_real_maid_passport_and_arrival_date_fields(): void
    {
        $maid = new Maid();
        $maid->forceFill([
            'name' => 'Test Worker',
            'passport_num' => 'BK555597',
            'arrival_date' => '2024-03-18',
        ]);

        $info = new ContractInfo();
        $info->forceFill([
            'arrival_date' => '2024-03-18',
            'contract_date' => '2024-03-04',
        ]);

        $contract = new Contract();
        $contract->setRelation('maid', $maid);
        $contract->setRelation('info', $info);
        $contract->setRelation('user', null);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('latestShelterLog', null);

        $method = new ReflectionMethod(ShelterContractController::class, 'buildDeportationDetails');
        $method->setAccessible(true);
        $details = $method->invoke(app(ShelterContractController::class), $contract);

        $this->assertSame('Test Worker', $details['maid_name']);
        $this->assertSame('BK555597', $details['passport_number']);
        $this->assertSame('2024-03-18', $details['arrival_date']);
        $this->assertSame('2024-03-04', $details['contract_date']);
    }
}
