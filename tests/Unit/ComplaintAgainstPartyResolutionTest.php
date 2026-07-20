<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ComplaintController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use Illuminate\Database\Eloquent\Collection;
use ReflectionMethod;
use Tests\TestCase;

class ComplaintAgainstPartyResolutionTest extends TestCase
{
    public function test_worker_is_the_against_party_for_an_order(): void
    {
        $user = (new User())->forceFill(['name' => 'Client Name', 'phone' => '551111111']);
        $maid = (new Maid())->forceFill([
            'name' => 'Worker Name',
            'passport_num' => 'P12345',
            'nationality_id' => 7,
        ]);
        $order = (new Order())->setRelation('user', $user)
            ->setRelation('maid', $maid)
            ->setRelation('contracts', new Collection());

        $party = $this->invokeResolver('resolveOrderAgainstParty', $order, 'worker');

        $this->assertSame('Worker Name', $party['name']);
        $this->assertNull($party['phone']);
        $this->assertSame('P12345', $party['maid_passport']);
        $this->assertSame(7, $party['maid_nationality_id']);
    }

    public function test_worker_is_resolved_from_manual_contract_data_when_no_maid_record_exists(): void
    {
        $info = (new ContractInfo())->forceFill([
            'manual_maid_name' => 'Manual Worker',
            'manual_maid_passport_num' => 'M9988',
            'manual_maid_nationality_id' => 12,
        ]);
        $contract = (new Contract())->setRelation('user', null)
            ->setRelation('maid', null)
            ->setRelation('info', $info);

        $party = $this->invokeResolver('resolveContractAgainstParty', $contract, 'worker');

        $this->assertSame('Manual Worker', $party['name']);
        $this->assertSame('M9988', $party['maid_passport']);
        $this->assertSame(12, $party['maid_nationality_id']);
    }

    public function test_client_against_party_keeps_the_existing_customer_logic(): void
    {
        $user = (new User())->forceFill(['name' => 'Client Name', 'phone' => '551111111']);
        $order = (new Order())->setRelation('user', $user);

        $party = $this->invokeResolver('resolveOrderAgainstParty', $order, 'client');

        $this->assertSame('Client Name', $party['name']);
        $this->assertSame('551111111', $party['phone']);
    }

    private function invokeResolver(string $method, object $relation, string $againstType): array
    {
        $resolver = new ReflectionMethod(ComplaintController::class, $method);
        $resolver->setAccessible(true);

        return $resolver->invoke(new ComplaintController(), $relation, $againstType);
    }
}
