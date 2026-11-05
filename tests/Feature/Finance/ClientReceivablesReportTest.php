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
        $rows = $service->query(['client_id' => $this->clientId, 'state' => 'closed'])->get();
        $this->assertCount(1, $rows);
        $this->assertEquals(900, $rows->first()->remaining);
        $this->assertCount(0, $service->query(['client_id' => $this->clientId, 'state' => 'open'])->get());
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
        $row = $service->query(['client_id' => $this->clientId, 'state' => 'open', 'extended' => '1'])->first();
        $this->assertNotNull($row);
        $this->assertEquals(2900, $row->billed);
        $this->assertEquals(1100, $row->paid);
        $this->assertEquals(300, $row->adjustments);
        $this->assertEquals(1500, $row->remaining);
        $this->assertSame('2031-04-30', $row->effective_end);
        $this->assertEquals(1500, $service->documents([$this->contractId])->get($this->contractId)->sum('remaining_amount'));
        $this->assertCount(0, $service->query(['client_id' => $this->clientId, 'state' => 'ended'])->get());
        $this->assertCount(0, $service->query(['client_id' => $this->clientId, 'extended' => '0'])->get());
    }

    public function test_settled_contract_is_hidden_by_default_and_visible_in_all_contracts(): void
    {
        DB::table('rental_contract_invoices')->where('rental_contract_id', $this->contractId)->update(['paid_amount' => 1900, 'remaining_amount' => 0]);
        $service = app(ClientReceivablesService::class);
        $this->assertCount(0, $service->query(['client_id' => $this->clientId])->get());
        $this->assertCount(1, $service->query(['client_id' => $this->clientId, 'balance' => 'all'])->get());
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
        $this->assertCount(0, $service->query(['client_id' => $this->clientId])->get());
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
        $this->assertSame(1, $data['rows']->total());
        $this->assertCount(1, $data['documents']->get($this->contractId));
    }
}
