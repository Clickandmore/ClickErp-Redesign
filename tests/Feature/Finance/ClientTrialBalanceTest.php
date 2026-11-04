<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Services\ClientTrialBalanceService;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class ClientTrialBalanceTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;
    private array $ids = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = Admin::factory()->create();
        $this->actingAs($this->admin, 'admin');
        DB::table('fn_constraint_details')->delete();
        DB::table('fn_accounts')->where('type', AccountTypesEnum::Client->value)->delete();

        $parent = $this->account('99', 'العملاء اختبار', null, AccountTypesEnum::Clients->value);
        $this->ids = [
            'owes' => $this->account('99000001', 'عميل عليه رصيد', $parent),
            'credit' => $this->account('99000002', 'عميل له رصيد', $parent),
            'settled' => $this->account('99000003', 'عميل مسدد', $parent),
            'idle' => $this->account('99000004', 'عميل بلا حركة', $parent),
        ];
        $income = $this->account('98', 'إيراد اختبار', null, AccountTypesEnum::NormalAccount->value);

        $this->line($this->ids['owes'], '2025-12-20', 300, 0);           // أول المدة
        $this->line($this->ids['owes'], '2026-02-01', 1000, 0);
        $this->line($this->ids['owes'], '2026-03-01', 0, 400);           // الرصيد 900 مدين
        $this->line($this->ids['credit'], '2026-02-10', 0, 250);         // الرصيد 250 دائن
        $this->line($this->ids['settled'], '2026-02-15', 500, 0);
        $this->line($this->ids['settled'], '2026-02-16', 0, 500);        // الرصيد صفر
        $this->line($this->ids['owes'], '2026-02-02', 999, 0, 'pending');  // غير معتمد
        $this->line($income, '2026-02-01', 0, 1000);
    }

    private function account(string $code, string $name, ?int $parent = null, int $type = 7): int
    {
        return DB::table('fn_accounts')->insertGetId(['name' => $name, 'code' => $code, 'level' => $parent ? 2 : 1, 'type' => $type, 'status' => 'debtor',
            'operation_type' => 1, 'parent_id' => $parent, 'created_by_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function line(int $account, string $date, float $debit, float $credit, string $status = 'approved'): void
    {
        $constraint = DB::table('fn_constraints')->insertGetId(['code' => random_int(1000000, 9999999), 'type' => 1, 'date' => $date, 'created_at' => now(), 'updated_at' => now()]
            + (Schema::hasColumn('fn_constraints', 'approval_status') ? ['approval_status' => $status] : []));
        DB::table('fn_constraint_details')->insert(['constraint_id' => $constraint, 'account_id' => $account, 'date' => $date, 'type' => $debit ? 'debtor' : 'creditor',
            'debtor_value' => $debit, 'creditor_value' => $credit]);
    }

    private function report(array $input): array
    {
        $service = app(ClientTrialBalanceService::class);

        return $service->report($service->filters($input + ['from_date' => '2026-01-01', 'to_date' => '2026-12-31']));
    }

    private function codes(array $report): array
    {
        return $report['rows']->pluck('code')->map(fn ($c) => (string) $c)->all();
    }

    public function test_balances_opening_and_totals(): void
    {
        $report = $this->report([]);
        $owes = $report['rows']->firstWhere('id', $this->ids['owes']);
        $this->assertSame(300.0, $owes->opening_debit);
        $this->assertSame(1999.0, $owes->period_debit);
        $this->assertSame(1899.0, $owes->balance);
        $this->assertSame(4, $report['count']);
        $this->assertSame(1899.0, $report['totals']['closing_debit']);
        $this->assertSame(250.0, $report['totals']['closing_credit']);

        $approved = $this->report(['approved_only' => '1']);
        $this->assertSame(900.0, $approved['rows']->firstWhere('id', $this->ids['owes'])->balance);

        $noOpening = $this->report(['approved_only' => '1', 'with_opening' => '0']);
        $this->assertSame(600.0, $noOpening['rows']->firstWhere('id', $this->ids['owes'])->balance);
    }

    public function test_every_filter(): void
    {
        $this->assertSame(['99000001', '99000002'], $this->codes($this->report(['balance' => 'non_zero'])));
        $this->assertSame(['99000003', '99000004'], $this->codes($this->report(['balance' => 'zero'])));
        $this->assertSame(['99000001'], $this->codes($this->report(['balance' => 'debit'])));
        $this->assertSame(['99000002'], $this->codes($this->report(['balance' => 'credit'])));
        $this->assertSame(['99000004'], $this->codes($this->report(['movement' => 'without'])));
        $this->assertSame(['99000001', '99000002', '99000003'], $this->codes($this->report(['movement' => 'with'])));
        $this->assertSame(['99000003'], $this->codes($this->report(['search' => 'مسدد'])));
        $this->assertSame(['99000001'], $this->codes($this->report(['min_balance' => '500'])));
        $this->assertSame(['99000001', '99000002', '99000003', '99000004'], $this->codes($this->report(['balance' => 'bogus'])));
        $sorted = $this->report(['sort' => 'balance_desc']);
        $this->assertSame('99000001', (string) $sorted['rows']->first()->code);
        $this->assertSame('99000002', (string) $sorted['rows']->last()->code);
    }

    public function test_page_and_export_follow_the_same_filters_and_permissions(): void
    {
        $query = ['from_date' => '2026-01-01', 'to_date' => '2026-12-31', 'balance' => 'non_zero', 'approved_only' => '1'];
        $this->get(route('finance.client-trial-balance.index', $query))->assertForbidden();

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_client_trial_balance_view', 'guard_name' => 'admin']));
        $this->get(route('finance.client-trial-balance.index', $query))->assertOk()
            ->assertSee('عميل عليه رصيد')->assertSee('عميل له رصيد')->assertDontSee('عميل مسدد')->assertSee('رصيده لا يساوي صفر');
        $this->get(route('finance.client-trial-balance.export', $query))->assertForbidden();

        $this->admin->givePermissionTo(Permission::firstOrCreate(['name' => 'finance_client_trial_balance_export', 'guard_name' => 'admin']));
        $response = $this->get(route('finance.client-trial-balance.export', $query))->assertOk();
        $file = tempnam(sys_get_temp_dir(), 'ctb') . '.xlsx';
        file_put_contents($file, $response->streamedContent());
        $rows = IOFactory::load($file)->getActiveSheet()->toArray();
        @unlink($file);

        $text = json_encode($rows, JSON_UNESCAPED_UNICODE);
        $this->assertStringContainsString('رصيده لا يساوي صفر', $text);
        $this->assertStringContainsString('عميل عليه رصيد', $text);
        $this->assertStringContainsString('عميل له رصيد', $text);
        $this->assertStringNotContainsString('عميل مسدد', $text);
        $this->assertStringNotContainsString('عميل بلا حركة', $text);
        $total = collect($rows)->first(fn ($r) => ($r[2] ?? null) === 'الإجمالي');
        $this->assertEquals(900, $total[7]);
        $this->assertEquals(250, $total[8]);
    }
}
