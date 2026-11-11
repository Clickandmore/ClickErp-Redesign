<?php

namespace Tests\Feature\Finance;

use Tests\TestCase;

class AccountCostCenterBalanceTest extends TestCase
{
    public function test_cost_center_report_uses_net_debit_less_credit_for_mixed_accounts(): void
    {
        $rows = collect([
            $this->row('creditor', 0, 1426.00),
            $this->row('creditor', 0, 4287.00),
            $this->row('debtor', 142.25, 0),
            $this->row('debtor', 0, 18.55),
            $this->row('debtor', 9375.00, 0),
        ]);

        $html = view('finance::finance.account_cost_centers.result', [
            'data' => $rows,
            'linkSummary' => [],
        ])->render();

        $this->assertStringContainsString('3,785.70', $html);
        $this->assertStringNotContainsString('15,211.70', $html);
    }

    private function row(string $status, float $debtorValue, float $creditorValue): object
    {
        return (object) [
            'status' => $status,
            'debtor_value' => $debtorValue,
            'creditor_value' => $creditorValue,
            'account_name' => 'حساب تجريبي',
            'date' => '2026-04-18',
            'constraint_id' => 1,
            'constraints_type' => 1,
            'statement' => 'قيد تجريبي',
            'name' => 'النظام',
        ];
    }
}
