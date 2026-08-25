<?php

namespace Tests\Unit;

use Illuminate\Http\Request;
use Illuminate\View\View;
use Mockery;
use Nami\Finance\Enums\BondTypes;
use Nami\Finance\Enums\ConstraintTypiesEnum;
use Nami\Finance\Http\Controllers\BondQabdController;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\Constraint;
use Nami\Finance\Services\BondService;
use Tests\TestCase;

class BondQabdDirectShowTest extends TestCase
{
    public function test_direct_request_returns_the_full_details_page_instead_of_an_empty_response(): void
    {
        $bond = Mockery::mock(Bond::class)->makePartial();
        $bond->id = 159;
        $bond->code = 159;
        $bond->payment_type = 1;
        $bond->shouldReceive('loadMissing')->once()->andReturnSelf();

        $service = Mockery::mock(BondService::class);
        $service->shouldReceive('findStandardBondByCode')
            ->once()
            ->with(159, BondTypes::Qabd)
            ->andReturn($bond);
        $service->shouldReceive('getBondPaymentTypies')->once()->andReturn(collect());
        $service->shouldReceive('getAccounts')->once()->andReturn(collect());
        $service->shouldReceive('getCostCenters')->once()->andReturn(collect());
        $service->shouldReceive('getEditPaymentTypeView')->once()->with(1, 'qabd')->andReturn('');

        $response = app(BondQabdController::class)->show(
            Request::create('/finance/bonds-qabd/159', 'GET'),
            $service,
            159
        );

        $this->assertInstanceOf(View::class, $response);
        $this->assertSame('finance::finance.bond.qabd.details', $response->name());
        $this->assertSame($bond, $response->getData()['obj']);
    }

    public function test_receipt_constraint_links_to_the_visible_bond_code(): void
    {
        $bond = new Bond(['code' => 6344]);
        $bond->id = 13387;

        $constraint = new Constraint();
        $constraint->type = ConstraintTypiesEnum::QapdBond;
        $constraint->setRelation('bond', $bond);

        $this->assertSame(6344, $constraint->show_id);
    }
}
