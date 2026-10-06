<?php

namespace Tests\Unit;

use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Services\ingaz\RentalContractLedgerService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class RentalContractNetBalanceTest extends TestCase
{
    /** @dataProvider balances */
    public function test_net_balance_includes_receipts_extensions_and_adjustments(?float $ledgerBalance, float $expected): void
    {
        $contract = new RentalContract();
        $contract->setRelation('user', null);
        $contract->setRelation('info', new RentalContractInfo(['contract_value' => 1000]));
        $contract->setRelation('financeInvoice', null);
        $contract->setRelation('extensions', new Collection([$this->row(['extension_value' => 200])]));
        $contract->setRelation('financeBonds', new Collection([
            $this->row(['type' => 'qabd', 'is_confirm' => 1, 'value' => 400]),
            $this->row(['type' => 'qabd', 'is_confirm' => 0, 'value' => 900]),
            $this->row(['type' => 'sarf', 'is_confirm' => 1, 'value' => 300]),
        ]));
        $contract->setRelation('financialAdjustments', new Collection([
            $this->row(['context' => 'closure', 'type' => 'income', 'amount' => 100, 'constraint_id' => null]),
            $this->row(['context' => 'closure_correction', 'type' => 'expense', 'amount' => 25, 'constraint_id' => null]),
            $this->row(['context' => 'manual_credit_note', 'type' => 'expense', 'amount' => 50, 'constraint_id' => 10]),
            $this->row(['context' => 'other', 'type' => 'income', 'amount' => 999, 'constraint_id' => null]),
        ]));
        $service = $this->getMockBuilder(RentalContractLedgerService::class)
            ->onlyMethods(['balanceForContract'])->getMock();
        $service->method('balanceForContract')->willReturn($ledgerBalance);

        $this->assertSame($expected, $service->netBalanceForContract($contract));
    }

    public function balances(): array
    {
        return [
            'unposted contract uses documents' => [null, 825.0],
            'posted credit note is not subtracted twice' => [750.0, 825.0],
            'settled balance' => [-75.0, 0.0],
            'customer credit remains negative' => [-200.0, -125.0],
        ];
    }

    public function test_legacy_request_payout_is_counted_once_and_unconfirmed_or_other_contract_bonds_are_excluded(): void
    {
        $contract = new RentalContract(['id' => 50]);
        $contract->setRelation('financeInvoice', $this->row(['invoice_constraint_id' => 100]));
        $contract->setRelation('extensions', new Collection());
        $contract->setRelation('financialAdjustments', new Collection());
        $paid = $this->row(['id' => 1, 'type' => 'sarf', 'is_confirm' => 1, 'constraint_id' => 200, 'rental_contract_id' => null]);
        $pending = $this->row(['id' => 2, 'is_confirm' => 0, 'constraint_id' => 300]);
        $other = $this->row(['id' => 3, 'is_confirm' => 1, 'constraint_id' => 400, 'rental_contract_id' => 99]);
        $contract->setRelation('financeBonds', new Collection([$paid]));
        $requests = [];
        foreach ([$paid, $pending, $other, null] as $bond) {
            $request = $this->row([]);
            $request->setRelation('financeBond', $bond);
            $requests[] = $request;
        }
        $contract->setRelation('financialRequests', new Collection($requests));
        $this->assertSame([100, 200], (new RentalContractLedgerService())->constraintIdsForContract($contract)->all());
    }

    public function test_confirmed_client_payout_settles_the_balance_without_an_invoice_journal(): void
    {
        $contract = new RentalContract(['id' => 50]);
        $contract->setRelation('user', $this->row(['account_id' => 9]));
        $contract->setRelation('info', new RentalContractInfo(['contract_value' => 3300]));
        $contract->setRelation('financeInvoice', null);
        $contract->setRelation('extensions', new Collection());
        $contract->setRelation('financialAdjustments', new Collection([$this->row(['context' => 'closure', 'type' => 'expense', 'amount' => 3300])]));
        $contract->setRelation('financeBonds', new Collection([$this->row(['id' => 1, 'type' => 'qabd', 'is_confirm' => 1, 'value' => 3300])]));
        $bond = $this->row(['id' => 2, 'type' => 'sarf', 'is_confirm' => 1, 'value' => 3300, 'debit_account_id' => 9]);
        $request = $this->row([]);
        $request->setRelation('financeBond', $bond);
        $contract->setRelation('financialRequests', new Collection([$request]));
        $service = $this->getMockBuilder(RentalContractLedgerService::class)->onlyMethods(['balanceForContract'])->getMock();
        $service->method('balanceForContract')->willReturn(null);
        $this->assertSame(0.0, $service->netBalanceForContract($contract));
        $bond->is_confirm = 0;
        $this->assertSame(-3300.0, $service->netBalanceForContract($contract));
    }

    private function row(array $attributes): Model
    {
        return new class($attributes) extends Model {
            protected $guarded = [];
        };
    }
}
