<?php

namespace Tests\Unit;

use App\Support\RentalClosureReconciler;
use PHPUnit\Framework\TestCase;

class RentalClosureReconcilerTest extends TestCase
{
    /** @dataProvider closureCases */
    public function test_receivable_and_cash_axes_never_get_mixed(
        float $invoiced,
        float $paid,
        float $earned,
        float $creditNote,
        float $debitNote,
        float $refund,
        float $toCollect,
        float $expectedBalance
    ): void {
        $result = RentalClosureReconciler::settle($invoiced, $paid, $earned);

        $this->assertSame($creditNote, $result['credit_note']);
        $this->assertSame($debitNote, $result['debit_note']);
        $this->assertSame($refund, $result['refund_to_client']);
        $this->assertSame($toCollect, $result['to_collect']);
        $this->assertSame($expectedBalance, $result['expected_balance']);
    }

    public static function closureCases(): array
    {
        return [
            'fully used and paid' => [1700, 1700, 1700, 0.0, 0.0, 0.0, 0.0, 0.0],
            'paid and closed early' => [1700, 1700, 793.33, 906.67, 0.0, 906.67, 0.0, -906.67],
            'unpaid and closed early' => [1700, 0, 793.33, 906.67, 0.0, 0.0, 793.33, 793.33],
            'partially paid' => [1700, 850, 56.67, 1643.33, 0.0, 793.33, 0.0, -793.33],
            'kept after expiry' => [1700, 1700, 2266.67, 0.0, 566.67, 0.0, 566.67, 566.67],
        ];
    }

    public function test_vat_rate_remains_fifteen_percent_when_note_exceeds_invoice(): void
    {
        $split = RentalClosureReconciler::splitByComposition(566.67, 0.25);

        $this->assertSame(15.0, $split['effective_rate']);
        $this->assertSame(566.67, $split['total']);
    }
}
