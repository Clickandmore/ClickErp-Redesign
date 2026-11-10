<?php

namespace Tests\Feature\Finance;

use App\Http\Middleware\EnsurePackageRoutePermission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\ClientReceivablesService;
use Tests\TestCase;

class ClientReceivablesReportTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private int $clientId;
    private int $contractId;

    protected function setUp(): void
    {
        parent::setUp();
        $this->travelTo(now()->setDate(2031, 3, 31)->startOfDay());
        $this->clientId = DB::table('users')->insertGetId(['name' => 'عميل المستحقات', 'phone_code' => '966', 'phone' => '500000039', 'password' => bcrypt('secret')]);
        $this->contractId = DB::table('rental_contracts')->insertGetId(['user_id' => $this->clientId, 'contract_number' => 'DUE-TEST-900', 'status' => 'completed', 'closed_at' => '2031-03-20']);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => $this->contractId, 'ingaz_from' => '2031-01-01', 'ingaz_to' => '2031-01-31']);
        DB::table('rental_contract_invoices')->insert([
            'rental_contract_id' => $this->contractId, 'total_due' => 1900, 'paid_amount' => 1000,
            'remaining_amount' => 900, 'is_paid' => false, 'payment_status' => 'partial', 'created_at' => '2031-01-01',
        ]);
    }

    public function test_closed_contract_keeps_its_900_receivable_and_can_be_found_by_client(): void
    {
        $service = app(ClientReceivablesService::class);
        $rows = $service->rows(['client_id' => $this->clientId, 'state' => 'closed']);
        $this->assertCount(1, $rows);
        $this->assertEquals(900, $rows->first()->remaining);
        $this->assertCount(0, $service->rows(['client_id' => $this->clientId, 'state' => 'open'])->all());
    }

    public function test_extension_and_closure_credit_are_counted_once_and_end_date_uses_extension(): void
    {
        DB::table('rental_contracts')->where('id', $this->contractId)->update(['status' => 'active', 'closed_at' => null]);
        DB::table('rental_contract_extensions')->insert([
            'rental_contract_id' => $this->contractId, 'extension_value' => 1000, 'payment_amount' => 100,
            'payment_status' => 'partial', 'start_date' => '2031-02-01', 'end_date' => '2031-04-30', 'created_at' => '2031-02-01',
        ]);
        DB::table('rental_financial_adjustments')->insert([
            'rental_contract_id' => $this->contractId, 'type' => 'expense', 'label' => 'Closure credit',
            'context' => 'closure', 'amount' => 300, 'issue_date' => '2031-03-01', 'created_at' => '2031-03-01',
        ]);
        $service = app(ClientReceivablesService::class);
        $row = $service->rows(['client_id' => $this->clientId, 'state' => 'open', 'extended' => '1'])->first();
        $this->assertNotNull($row);
        $this->assertEquals(2900, $row->billed);
        $this->assertEquals(1100, $row->paid);
        $this->assertEquals(300, $row->adjustments);
        $this->assertEquals(1500, $row->remaining);
        $this->assertSame('2031-04-30', $row->effective_end);
        $this->assertEquals(1500, $service->documents([$this->contractId])->get($this->contractId)->sum('remaining_amount'));
        $this->assertCount(0, $service->rows(['client_id' => $this->clientId, 'state' => 'ended'])->all());
        $this->assertCount(0, $service->rows(['client_id' => $this->clientId, 'extended' => '0'])->all());
    }

    public function test_settled_contract_is_hidden_by_default_and_visible_in_all_contracts(): void
    {
        DB::table('rental_contract_invoices')->where('rental_contract_id', $this->contractId)->update(['paid_amount' => 1900, 'remaining_amount' => 0]);
        $service = app(ClientReceivablesService::class);
        $this->assertCount(0, $service->rows(['client_id' => $this->clientId])->all());
        $this->assertCount(1, $service->rows(['client_id' => $this->clientId, 'balance' => 'all'])->all());
    }

    public function test_page_requires_its_own_permission_without_finance_module_fallback(): void
    {
        $this->assertSame(['finance_client_receivables_view'], app(EnsurePackageRoutePermission::class)->resolvePermissions('finance.client-receivables.index', 'finance'));
    }

    public function test_report_and_document_details_do_not_leak_other_branch_contracts(): void
    {
        $this->setUpBranches();
        $this->enableBranchesModule();
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($own));
        DB::table('rental_contracts')->where('id', $this->contractId)->update(['branch_id' => $other->id]);
        DB::table('rental_contract_invoices')->where('rental_contract_id', $this->contractId)->update(['branch_id' => $other->id]);
        $this->actingAsAdmin($admin);
        $service = app(ClientReceivablesService::class);
        $this->assertCount(0, $service->rows(['client_id' => $this->clientId])->all());
        $this->assertCount(0, $service->documents([$this->contractId]));
    }

    public function test_controller_returns_totals_documents_and_a_protected_registered_route(): void
    {
        $route = app('router')->getRoutes()->getByName('finance.client-receivables.index');
        $this->assertNotNull($route);
        $this->assertContains('package.route.permission:finance', $route->gatherMiddleware());
        $request = \Illuminate\Http\Request::create('/finance/client-receivables', 'GET', ['client_id' => $this->clientId]);
        $view = app(\Nami\Finance\Http\Controllers\ClientReceivablesController::class)->index($request, app(ClientReceivablesService::class));
        $data = $view->getData();
        $this->assertEquals(900, $data['totals']->due);
        $this->assertSame(1, $data['rows']->total(), 'صف واحد لكل عميل.');
        $this->assertCount(1, $data['rows']->first()->contracts);
        $this->assertCount(1, $data['documents']->get($this->contractId));
    }

    /** حساب محاسبي للعميل حتى تُقرأ أرصدته من دفتر الأستاذ. */
    private function giveClientAccount(): int
    {
        $accountId = DB::table('fn_accounts')->insertGetId([
            'name' => 'حساب عميل المستحقات', 'operation_type' => 1, 'status' => 'debtor',
            'level' => 5, 'code' => random_int(70000000, 79999999), 'type' => 1,
            'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('users')->where('id', $this->clientId)->update(['account_id' => $accountId]);

        return $accountId;
    }

    /** قيد على حساب العميل: موجب = مدين (مطالبة)، سالب = دائن (سداد/إشعار). */
    private function clientEntry(int $accountId, string $date, float $amount, string $approval = \Nami\Finance\Models\Constraint::APPROVAL_APPROVED): int
    {
        $constraintId = DB::table('fn_constraints')->insertGetId([
            'type' => 1, 'code' => random_int(900000, 999999), 'value' => abs($amount), 'date' => $date,
            'statement' => 'قيد اختبار المستحقات', 'approval_status' => $approval, 'created_at' => now(), 'updated_at' => now(),
        ]);
        DB::table('fn_constraint_details')->insert([
            'constraint_id' => $constraintId, 'account_id' => $accountId, 'date' => $date,
            'type' => $amount >= 0 ? 'debtor' : 'creditor',
            'debtor_value' => max($amount, 0), 'creditor_value' => max(-$amount, 0),
            'statement' => 'قيد اختبار المستحقات', 'created_at' => now(), 'updated_at' => now(),
        ]);

        return $constraintId;
    }

    private function receiptBond(int $accountId, string $date, float $amount, ?int $contractId = null): void
    {
        $constraintId = $this->clientEntry($accountId, $date, -$amount);
        DB::table('fn_bonds')->insert([
            'type' => 'qabd', 'payment_type' => 'cash', 'code' => random_int(900000, 999999), 'value' => $amount,
            'date' => $date, 'debit_account_id' => $accountId, 'credit_account_id' => $accountId, 'created_by_id' => 1,
            'is_confirm' => 1, 'constraint_id' => $constraintId, 'rental_contract_id' => $contractId ?: $this->contractId,
            'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** الفاتورة مرحّلة: قيدها على حساب العميل بقيمتها. */
    private function postInvoice(int $accountId): void
    {
        $constraintId = $this->clientEntry($accountId, '2031-01-01', 1900);
        DB::table('rental_contract_invoices')->where('rental_contract_id', $this->contractId)
            ->update(['invoice_constraint_id' => $constraintId]);
    }

    public function test_receipt_bond_not_reflected_on_invoice_still_settles_the_contract(): void
    {
        // سند قبض ميسر/إضافي لا يحدّث paid_amount في الفاتورة، لكنه حركة فعلية على العقد.
        $accountId = $this->giveClientAccount();
        $this->postInvoice($accountId);
        $this->receiptBond($accountId, '2031-01-05', 1000);
        DB::table('rental_contract_invoices')->where('rental_contract_id', $this->contractId)->update(['paid_amount' => 0, 'remaining_amount' => 1900]);
        $this->receiptBond($accountId, '2031-02-10', 600);

        $row = app(ClientReceivablesService::class)->rows(['client_id' => $this->clientId, 'balance' => 'all'])->first();
        $this->assertEquals(1900, $row->billed);
        $this->assertEquals(1600, $row->paid);
        $this->assertEquals(300, $row->remaining);
        $this->assertEquals(300, $row->client_ledger_balance, 'رصيد العقد = رصيد حساب العميل في الأستاذ.');
    }

    public function test_unlinked_credit_and_pending_entries_on_client_account_are_reconciled(): void
    {
        $accountId = $this->giveClientAccount();
        $this->postInvoice($accountId);
        $this->receiptBond($accountId, '2031-01-05', 1000);
        // سداد بقيد يدوي على حساب العميل دون ربطه بالعقد، ومطالبة بانتظار الاعتماد.
        $this->clientEntry($accountId, '2031-02-01', -500);
        $this->clientEntry($accountId, '2031-03-01', 250, \Nami\Finance\Models\Constraint::APPROVAL_PENDING);

        $service = app(ClientReceivablesService::class);
        $rows = $service->rows(['client_id' => $this->clientId, 'balance' => 'all']);
        $row = $rows->first();
        $this->assertEquals(650, $row->remaining, '1900 − 1000 − 500 + 250 المعلّق.');
        $this->assertEquals(400, $row->client_ledger_balance);
        $this->assertEquals(250, $row->client_pending);

        $recon = $service->totals($rows)->reconciliation;
        $this->assertEquals(
            $recon->rental_net,
            round($recon->ledger + $recon->pending - $recon->other_business + $recon->unposted - $recon->unbacked - $recon->mixed_cap, 2)
        );
    }

    public function test_overpaid_contract_covers_the_same_clients_other_contract(): void
    {
        $accountId = $this->giveClientAccount();
        $other = DB::table('rental_contracts')->insertGetId(['user_id' => $this->clientId, 'contract_number' => 'DUE-TEST-901', 'status' => 'completed']);
        DB::table('rental_contract_invoices')->insert([
            'rental_contract_id' => $other, 'total_due' => 1000, 'paid_amount' => 0,
            'remaining_amount' => 1000, 'is_paid' => false, 'payment_status' => 'later', 'created_at' => '2031-02-01',
        ]);
        $this->receiptBond($accountId, '2031-02-02', 1400, $other);

        $rows = app(ClientReceivablesService::class)->rows(['client_id' => $this->clientId, 'balance' => 'all'])->keyBy('id');
        $this->assertEquals(500, $rows[$this->contractId]->remaining, 'الـ 400 الزائدة على العقد الآخر تغطي هذا العقد.');
        $this->assertEquals(-400, $rows[$this->contractId]->offsets);
        $this->assertEquals(0, $rows[$other]->remaining);
    }

    public function test_clients_are_listed_newest_first_with_their_unsettled_previous_contracts(): void
    {
        $older = $this->contractId;
        $newer = DB::table('rental_contracts')->insertGetId(['user_id' => $this->clientId, 'contract_number' => 'DUE-TEST-902', 'status' => 'active']);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => $newer, 'ingaz_from' => '2031-03-01', 'ingaz_to' => '2031-03-31']);
        DB::table('rental_contract_invoices')->insert([
            'rental_contract_id' => $newer, 'total_due' => 500, 'paid_amount' => 0,
            'remaining_amount' => 500, 'is_paid' => false, 'payment_status' => 'later', 'created_at' => '2031-03-01',
        ]);

        $service = app(ClientReceivablesService::class);
        $rows = $service->rows(['client_id' => $this->clientId]);
        $this->assertSame([$newer, $older], $rows->pluck('id')->all(), 'الأحدث أولاً افتراضياً.');
        $this->assertSame([$older, $newer], $service->rows(['client_id' => $this->clientId, 'sort' => 'amount'])->pluck('id')->all());

        $client = $service->clients($rows)->first();
        $this->assertCount(2, $client->contracts, 'العقد السابق غير المصفّى يظهر تحت نفس العميل.');
        $this->assertEquals(1400, $client->net);
        $this->assertSame('2031-03-01', $client->newest_contract_date);
    }
}
