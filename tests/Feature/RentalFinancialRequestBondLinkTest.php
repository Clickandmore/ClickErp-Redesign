<?php
namespace Tests\Feature;

use App\Http\Controllers\ingaz\FinancialRequestController;
use App\Models\FinancialRequest;
use App\Models\auth\Admin;
use App\Support\AutomaticJournalEntries;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nami\Finance\Models\Bond;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Services\BondService;
use Nami\Finance\Services\YearService;
use Tests\TestCase;

class RentalFinancialRequestBondLinkTest extends TestCase
{
    use DatabaseTransactions;

    public function test_rental_request_passes_its_contract_to_the_confirmed_payment_bond(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        ConfirmSetting::query()->where('title', AutomaticJournalEntries::SETTING_KEY)->delete();
        $request = \Mockery::mock(FinancialRequest::class)->makePartial();
        $request->forceFill(['contract_type' => 'rental', 'contract_id' => 123, 'amount' => 3300,
            'request_no' => 'FR-TEST', 'request_date' => now()->toDateString(), 'type' => 'client_refund']);
        $request->shouldReceive('update')->once()->with(['finance_bond_id' => 999, 'finance_bond_type' => 'sarf'])->andReturn(true);
        $bonds = \Mockery::mock(BondService::class);
        $bond = new Bond();
        $bond->id = 999;
        $bonds->shouldReceive('lastCode')->once()->with('sarf')->andReturn(1);
        $bonds->shouldReceive('storeSarfBond')->once()->with(\Mockery::on(function ($data) {
            $this->assertSame(123, $data['rental_contract_id']);
            $this->assertSame(3300.0, $data['value']);
            return true;
        }))->andReturn($bond);
        $bonds->shouldReceive('confirmBond')->once()->with(999);
        $year = \Mockery::mock(YearService::class);
        $year->shouldReceive('checkDateRange')->once()->andReturn(true);
        $method = new \ReflectionMethod(FinancialRequestController::class, 'createFinanceBond');
        $method->setAccessible(true);
        $method->invoke(app(FinancialRequestController::class), $request,
            ['bond_type' => 'sarf', 'debit_account_id' => 1, 'credit_account_id' => 2], $bonds, $year);
    }
}
