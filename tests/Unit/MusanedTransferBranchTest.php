<?php

namespace Tests\Unit;

use App\Support\MusanedTransferBranch;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class MusanedTransferBranchTest extends TestCase
{
    private function invoice(?int $contract, ?int $invoice, ?int $delegation = null): object
    {
        return (object) ['branch_id' => $invoice, 'contract' => (object) ['branch_id' => $contract], 'ingazDelegation' => (object) ['branch_id' => $delegation]];
    }

    public function test_documents_take_precedence_over_employee(): void
    {
        $this->assertSame(2, MusanedTransferBranch::resolve([$this->invoice(2, 2)], 1));
        $this->assertSame(2, MusanedTransferBranch::resolve([$this->invoice(null, 2)], 1));
        $this->assertSame(2, MusanedTransferBranch::resolve([$this->invoice(null, null, 2)], 1));
        $this->assertSame(2, MusanedTransferBranch::resolve([$this->invoice(null, null), $this->invoice(2, 2)], 1));
    }

    public function test_employee_is_last_fallback(): void
    {
        $this->assertSame(1, MusanedTransferBranch::resolve([$this->invoice(null, null)], 1));
        $this->assertNull(MusanedTransferBranch::resolve([$this->invoice(null, null)], null));
    }

    public function test_mixed_branches_are_rejected_by_the_single_branch_resolver(): void
    {
        $this->expectException(ValidationException::class);
        MusanedTransferBranch::resolve([$this->invoice(1, 1), $this->invoice(2, 2)], 1);
    }

    public function test_conflicting_contract_and_invoice_are_rejected(): void
    {
        $this->expectException(ValidationException::class);
        MusanedTransferBranch::resolve([$this->invoice(2, 1)], 1);
    }
}
