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

    private function row(array $attributes): Model
    {
        return new class($attributes) extends Model {
            protected $guarded = [];
        };
    }
}
