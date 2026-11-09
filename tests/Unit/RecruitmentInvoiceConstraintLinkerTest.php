<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use Illuminate\Container\Container;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use Mockery;
use PHPUnit\Framework\TestCase;

class RecruitmentInvoiceConstraintLinkerTest extends TestCase
{
    public function test_credit_note_reuses_verified_source_instead_of_creating_a_journal(): void
    {
        $container = Container::getInstance();
        $linker = Mockery::mock(LegacyInvoiceConstraintLinker::class);
        $invoice = Mockery::mock(Invoice::class)->makePartial();
        $source = new \Nami\Finance\Models\Constraint(['id' => 2810]);
        $linker->shouldReceive('link')->once()->with($invoice)->andReturn($source);
        $invoice->shouldReceive('refresh')->once()->andReturnSelf();
        $container->instance(LegacyInvoiceConstraintLinker::class, $linker);
        try {
            $class = new \ReflectionClass(\App\Http\Controllers\ingaz\InvoiceController::class);
            $method = $class->getMethod('ensureRecruitmentInvoiceConstraintForCreditNote');
            $method->setAccessible(true);
            $parameters = $method->getParameters();
            $result = $method->invoke($class->newInstanceWithoutConstructor(), $invoice,
                Mockery::mock($parameters[1]->getType()->getName()),
                Mockery::mock($parameters[2]->getType()->getName()));
            $this->assertSame($source, $result);
        } finally {
            $container->forgetInstance(LegacyInvoiceConstraintLinker::class);
        }
    }

    public function test_native_source_lookup_includes_both_contract_and_invoice_identifiers(): void
    {
        $query = Mockery::mock();
        DB::shouldReceive('table')->with('fn_constraints')->once()->andReturn($query);
        $query->shouldReceive('whereNull')->with('deleted_at')->once()->andReturnSelf();
        $query->shouldReceive('where')->with(Mockery::on(function ($callback) {
            $identity = Mockery::mock();
            $identity->shouldReceive('where')->once()->with('statement', 'like',
                'قيد فاتورة طلب استقدام للعقد #546 / الفاتورة #480 / مساند %');
            $callback($identity);
            return true;
        }))->once()->andReturnSelf();
        $query->shouldReceive('get')->once()->andReturn(collect());
        // Another invoice exists for the contract, so the re-issued fallback is skipped.
        $invoices = Mockery::mock();
        $invoices->shouldReceive('where')->with('contract_id', 546)->once()->andReturnSelf();
        $invoices->shouldReceive('where')->with('id', '!=', 480)->once()->andReturnSelf();
        $invoices->shouldReceive('exists')->once()->andReturn(true);
        DB::shouldReceive('table')->with('invoices')->once()->andReturn($invoices);
        $this->assertSame('missing', (new LegacyInvoiceConstraintLinker())->inspect($this->invoice())['status']);
    }

    /** @dataProvider reissuedInvoices */
    public function test_journal_of_a_reissued_invoice_is_considered_only_when_the_old_invoice_is_gone(bool $oldInvoiceExists, string $status): void
    {
        $exact = Mockery::mock();
        $exact->shouldReceive('whereNull')->andReturnSelf();
        $exact->shouldReceive('where')->andReturnSelf();
        $exact->shouldReceive('get')->andReturn(collect());
        $fallback = Mockery::mock();
        $fallback->shouldReceive('whereNull')->andReturnSelf();
        $fallback->shouldReceive('where')->once()
            ->with('statement', 'like', 'قيد فاتورة طلب استقدام للعقد #546 / الفاتورة #%')->andReturnSelf();
        $fallback->shouldReceive('get')->andReturn(collect([(object) [
            'id' => 2810, 'legacy_invoice_number' => null, 'branch_id' => 1, 'value' => 4000,
            'statement' => 'قيد فاتورة طلب استقدام للعقد #546 / الفاتورة #482 / مساند 77',
        ]]));
        DB::shouldReceive('table')->with('fn_constraints')->andReturn($exact, $fallback);
        $invoices = Mockery::mock();
        $invoices->shouldReceive('where')->andReturnSelf();
        // sibling invoice on contract, old invoice id, source already linked
        $invoices->shouldReceive('exists')->andReturn(false, $oldInvoiceExists, false);
        DB::shouldReceive('table')->with('invoices')->andReturn($invoices);

        // value 4000 ≠ 3999 stops at the header check, proving the journal was picked up.
        $this->assertSame($status, (new LegacyInvoiceConstraintLinker())->inspect($this->invoice())['status']);
    }

    public static function reissuedInvoices(): array
    {
        return [[false, 'header_mismatch'], [true, 'missing']];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Facade::setFacadeApplication(new Container());
        DB::swap(Mockery::mock(\Illuminate\Database\DatabaseManager::class));
    }

    protected function tearDown(): void
    {
        Mockery::close();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        parent::tearDown();
    }

    private function invoice(): Invoice
    {
        $invoice = new Invoice(['id' => 480, 'contract_id' => 546, 'total_due' => 3999, 'branch_id' => 1]);
        $contract = new Contract();
        $contract->setRelation('user', new User(['account_id' => 90]));
        $invoice->setRelation('contract', $contract);
        return $invoice;
    }

    private function source(array $overrides = []): object
    {
        return (object) array_merge(['id' => 2810, 'legacy_invoice_number' => null, 'branch_id' => 1, 'value' => 3999], $overrides);
    }

    public function test_missing_and_duplicate_sources_are_rejected_without_creating_entries(): void
    {
        $linker = new LegacyInvoiceConstraintLinker();
        $this->assertSame('missing', $linker->inspect($this->invoice(), collect())['status']);
        $this->assertSame('ambiguous', $linker->inspect($this->invoice(), collect([$this->source(), $this->source(['id' => 6723])]))['status']);
    }

    /** @dataProvider mismatchedHeaders */
    public function test_wrong_amount_or_branch_is_rejected(array $overrides): void
    {
        DB::shouldReceive('table->where->where->exists')->once()->andReturn(false);
        $this->assertSame('header_mismatch', (new LegacyInvoiceConstraintLinker())->inspect($this->invoice(), collect([$this->source($overrides)]))['status']);
    }

    public static function mismatchedHeaders(): array
    {
        return [[['branch_id' => 2]], [['value' => 4000]]];
    }

    public function test_source_linked_to_another_invoice_is_rejected(): void
    {
        DB::shouldReceive('table->where->where->exists')->once()->andReturn(true);
        $this->assertSame('source_already_linked', (new LegacyInvoiceConstraintLinker())->inspect($this->invoice(), collect([$this->source()]))['status']);
    }

    /** @dataProvider customerAccounts */
    public function test_only_balanced_entries_for_the_correct_customer_are_ready(int $customerAccount, string $status): void
    {
        $invoices = Mockery::mock();
        $invoices->shouldReceive('where')->andReturnSelf();
        $invoices->shouldReceive('exists')->andReturn(false);
        DB::shouldReceive('table')->with('invoices')->andReturn($invoices);
        $details = Mockery::mock();
        $details->shouldReceive('where->whereNull->get')->andReturn(collect([
            (object) ['branch_id' => 1, 'account_id' => $customerAccount, 'debtor_value' => 3999, 'creditor_value' => 0],
            (object) ['branch_id' => 1, 'account_id' => 100, 'debtor_value' => 0, 'creditor_value' => 3999],
        ]));
        DB::shouldReceive('table')->with('fn_constraint_details')->andReturn($details);
        if ($status === 'ready') {
            $accounts = Mockery::mock();
            $accounts->shouldReceive('where->first')->andReturn((object) ['name' => 'التزامات عقود', 'parent_id' => null]);
            DB::shouldReceive('table')->with('fn_accounts')->andReturn($accounts);
        }
        $result = (new LegacyInvoiceConstraintLinker())->inspect($this->invoice(), collect([$this->source()]));
        $this->assertSame($status, $result['status']);
    }

    public static function customerAccounts(): array
    {
        return [[90, 'ready'], [91, 'lines_mismatch']];
    }
}
