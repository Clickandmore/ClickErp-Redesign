<?php

namespace Tests\Unit;

use App\Console\Commands\RepairRecruitmentInvoiceLinks;
use App\Models\Contract;
use App\Models\ingaz\Invoice;
use App\Models\ingaz\User;
use App\Services\ingaz\LegacyInvoiceConstraintLinker;
use Illuminate\Container\Container;
use Illuminate\Database\Capsule\Manager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Facade;
use PHPUnit\Framework\TestCase;

class RecruitmentInvoiceRepairCommandTest extends TestCase
{
    private $previousResolver;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousResolver = Invoice::getConnectionResolver();
        $capsule = new Manager(new Container());
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->bootEloquent();
        Facade::setFacadeApplication(new Container());
        DB::swap($capsule->getDatabaseManager());
        \App\Support\ConstraintReferenceGuard::flushSchemaCache();
        DB::statement('CREATE TABLE invoices (id INTEGER, invoice_constraint_id INTEGER)');
        DB::statement('CREATE TABLE fn_constraints (id INTEGER, code INTEGER, statement TEXT, date TEXT, branch_id INTEGER, year_id INTEGER, approval_status TEXT, created_at TEXT, value REAL, deleted_at TEXT, legacy_invoice_number INTEGER)');
        DB::statement('CREATE TABLE fn_constraint_details (id INTEGER, constraint_id INTEGER, account_id INTEGER, branch_id INTEGER, cost_center_id INTEGER, debtor_value REAL, creditor_value REAL, deleted_at TEXT)');
        DB::statement('CREATE TABLE fn_accounts (id INTEGER, name TEXT, parent_id INTEGER)');
        DB::statement('CREATE TABLE fn_constraint_logs (constraint_id INTEGER, branch_id INTEGER, action TEXT, metadata TEXT, admin_id INTEGER, description TEXT, created_at TEXT, updated_at TEXT)');
        DB::table('invoices')->insert(['id' => 480, 'invoice_constraint_id' => 6723]);
        DB::table('fn_accounts')->insert(['id' => 100, 'name' => 'التزامات عقود', 'parent_id' => null]);
        foreach ([[2810, 2802, '2026-07-21'], [6723, 6722, '2026-11-02']] as [$id, $code, $created]) {
            DB::table('fn_constraints')->insert(['id' => $id, 'code' => $code, 'statement' => 'قيد فاتورة طلب استقدام للعقد #546 / الفاتورة #480 / مساند 13412394',
                'date' => '2026-07-21', 'branch_id' => 1, 'year_id' => 1, 'approval_status' => 'approved', 'created_at' => $created, 'value' => 3999]);
            DB::table('fn_constraint_details')->insert([
                ['constraint_id' => $id, 'account_id' => 90, 'branch_id' => 1, 'cost_center_id' => 5, 'debtor_value' => 3999, 'creditor_value' => 0],
                ['constraint_id' => $id, 'account_id' => 100, 'branch_id' => 1, 'cost_center_id' => 5, 'debtor_value' => 0, 'creditor_value' => 3999],
            ]);
        }
    }

    protected function tearDown(): void
    {
        if ($this->previousResolver) Invoice::setConnectionResolver($this->previousResolver);
        else Invoice::unsetConnectionResolver();
        Facade::clearResolvedInstances();
        Facade::setFacadeApplication(null);
        \Mockery::close();
        parent::tearDown();
    }

    private function plan(): array
    {
        $invoice = new Invoice(['id' => 480, 'contract_id' => 546, 'invoice_constraint_id' => DB::table('invoices')->where('id', 480)->value('invoice_constraint_id'), 'total_due' => 3999, 'branch_id' => 1]);
        $contract = new Contract();
        $contract->setRelation('user', new User(['account_id' => 90]));
        $invoice->setRelation('contract', $contract);
        $method = new \ReflectionMethod(RepairRecruitmentInvoiceLinks::class, 'duplicatePlan');
        $method->setAccessible(true);
        return $method->invoke(new RepairRecruitmentInvoiceLinks(), $invoice, new LegacyInvoiceConstraintLinker());
    }

    public function test_exact_audited_duplicate_is_ready_and_preview_changes_nothing(): void
    {
        $this->assertSame('ready', $this->plan()['status']);
        $this->assertSame(2, DB::table('fn_constraints')->count());
        $this->assertSame(0, DB::table('fn_constraint_logs')->count());
    }

    public function test_changed_detail_blocks_reversal(): void
    {
        DB::table('fn_constraint_details')->where('constraint_id', 6723)->where('account_id', 100)->update(['cost_center_id' => 6]);
        $this->assertSame('duplicate_fingerprint_mismatch', $this->plan()['status']);
    }

    public function test_original_linked_elsewhere_blocks_reversal(): void
    {
        DB::table('invoices')->insert(['id' => 999, 'invoice_constraint_id' => 2810]);
        $this->assertSame('original_source_already_linked', $this->plan()['status']);
    }

    public function test_pending_duplicate_cannot_be_reversed(): void
    {
        DB::table('fn_constraints')->where('id', 6723)->update(['approval_status' => 'pending']);
        $this->assertSame('duplicate_fingerprint_mismatch', $this->plan()['status']);
    }

    public function test_duplicate_used_by_another_document_is_blocked(): void
    {
        DB::statement('CREATE TABLE fn_bonds (id INTEGER, constraint_id INTEGER)');
        DB::table('fn_bonds')->insert(['id' => 1, 'constraint_id' => 6723]);
        \App\Support\ConstraintReferenceGuard::flushSchemaCache();
        $this->assertSame('unexpected_duplicate_reference', $this->plan()['status']);
    }

    public function test_extra_journal_blocks_reversal(): void
    {
        $extra = (array) DB::table('fn_constraints')->where('id', 2810)->first();
        $extra['id'] = 9000;
        $extra['code'] = 9000;
        DB::table('fn_constraints')->insert($extra);
        $this->assertSame('unexpected_extra_journals', $this->plan()['status']);
    }

    public function test_invalid_prior_repair_log_blocks_repeated_reversal(): void
    {
        DB::table('fn_constraint_logs')->insert(['constraint_id' => 6723, 'action' => 'recruitment_duplicate_repair_v1', 'metadata' => '{}']);
        $this->assertSame('repair_audit_mismatch', $this->plan()['status']);
    }

    /** @dataProvider reversalApproval */
    public function test_reversal_is_verified_and_repeat_safe_or_entirely_rolled_back(string $approval): void
    {
        $plan = $this->plan();
        $invoice = \Mockery::mock(Invoice::class)->makePartial();
        $invoice->fill(['id' => 480, 'invoice_constraint_id' => 6723]);
        $invoice->shouldReceive('update')->with(\Mockery::on(function ($data) use ($invoice) {
            DB::table('invoices')->where('id', 480)->update(['invoice_constraint_id' => $data['invoice_constraint_id']]);
            $invoice->fill($data);
            return true;
        }))->andReturn(true);
        $years = \Mockery::mock(\Nami\Finance\Services\YearService::class);
        $years->shouldReceive('checkDateRange')->with('2026-11-08')->once()->andReturn((object) ['id' => 1]);
        $journals = \Mockery::mock(\Nami\Finance\Services\ConstraintService::class);
        $reversal = \Mockery::mock(\Nami\Finance\Models\Constraint::class)->makePartial();
        $reversal->setRawAttributes(['id' => 9000, 'approval_status' => $approval]);
        $reversal->shouldReceive('fresh')->andReturnSelf();
        $journals->shouldReceive('storeConstraint')->once()->andReturnUsing(function ($data) use ($approval, $reversal) {
            DB::table('fn_constraints')->insert(['id' => 9000, 'code' => 9000, 'statement' => $data['statement'], 'value' => $data['value'], 'approval_status' => $approval, 'date' => $data['date'], 'branch_id' => $data['branch_id'], 'year_id' => 1]);
            return $reversal;
        });
        $journals->shouldReceive('storeConstraintDetails')->once()->andReturnUsing(function ($ids, $id, $data) {
            foreach ($ids as $i => $account) {
                DB::table('fn_constraint_details')->insert(['constraint_id' => $id, 'account_id' => $account,
                    'branch_id' => $data['detail_branch_id'][$i], 'cost_center_id' => $data['cost_center_id'][$i],
                    'debtor_value' => $data['debtor_value'][$i], 'creditor_value' => $data['creditor_value'][$i]]);
            }
        });
        $method = new \ReflectionMethod(RepairRecruitmentInvoiceLinks::class, 'reverseDuplicate');
        $method->setAccessible(true);
        $command = \Mockery::mock(RepairRecruitmentInvoiceLinks::class)->makePartial();
        $command->shouldReceive('line')->andReturnNull();
        try {
            DB::transaction(fn () => $method->invoke($command, $invoice, $plan, $journals, $years, '2026-11-08', 1));
        } catch (\RuntimeException $e) {
            $this->assertSame('rejected', $approval);
            $this->assertSame('Unexpected reversal approval status.', $e->getMessage());
            $this->assertSame(2, DB::table('fn_constraints')->count());
            $this->assertSame(4, DB::table('fn_constraint_details')->count());
            $this->assertSame(0, DB::table('fn_constraint_logs')->count());
            $this->assertSame(6723, DB::table('invoices')->where('id', 480)->value('invoice_constraint_id'));
            return;
        }
        $this->assertSame(3, DB::table('fn_constraints')->count());
        if ($approval === 'pending') {
            $this->assertSame('awaiting_approval', $this->plan()['status']);
            $this->assertSame('awaiting_approval', $this->plan()['status']);
            $this->assertSame(6723, DB::table('invoices')->where('id', 480)->value('invoice_constraint_id'));
            DB::table('fn_constraints')->where('id', 9000)->update(['date' => '2026-11-09']);
            $this->assertSame('repair_audit_mismatch', $this->plan()['status']);
            DB::table('fn_constraints')->where('id', 9000)->update(['date' => '2026-11-08']);
            DB::table('fn_constraints')->where('id', 9000)->update(['approval_status' => 'approved']);
            $finalPlan = $this->plan();
            $this->assertSame('ready_to_finalize', $finalPlan['status']);
            $finalize = new \ReflectionMethod(RepairRecruitmentInvoiceLinks::class, 'finalizeDuplicate');
            $finalize->setAccessible(true);
            DB::transaction(fn () => $finalize->invoke($command, $invoice, $finalPlan, 1));
        }
        $this->assertSame('already_applied', $this->plan()['status']);
        $this->assertSame(3, DB::table('fn_constraints')->count());
        $this->assertSame(2810, DB::table('invoices')->where('id', 480)->value('invoice_constraint_id'));
        $this->assertSame(1, DB::table('fn_constraint_logs')->count());
    }

    public static function reversalApproval(): array
    {
        return [['approved'], ['pending'], ['rejected']];
    }
}
