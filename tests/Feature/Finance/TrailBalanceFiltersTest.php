<?php

namespace Tests\Feature\Finance;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Services\TrailBalanceService;
use Tests\TestCase;

class TrailBalanceFiltersTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        $admin = Admin::factory()->create();
        foreach (['finance_module', 'finance_trail_balance_export'] as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }
        $this->actingAs($admin, 'admin');
        DB::table('fn_constraint_details')->delete();
        DB::table('fn_accounts')->delete();
        if (!DB::table('fn_years')->exists()) {
            DB::table('fn_years')->insert(['title' => '2026', 'from_date' => '2026-01-01', 'to_date' => '2026-12-31', 'status' => 1, 'created_by_id' => 1]);
        }

        $assets = $this->account('1', 'الأصول', null, 1, 'debtor');
        $cash = $this->account('11', 'صندوق اختبار', $assets, 2, 'debtor');
        $this->account('12', 'بنك فارغ', $assets, 2, 'debtor');
        $income = $this->account('4', 'الإيرادات', null, 1, 'creditor');
        $sales = $this->account('41', 'مبيعات اختبار', $income, 2, 'creditor');
        $settled = $this->account('42', 'إيراد مسدد', $income, 2, 'creditor');
        $this->line($cash, 500, 0);
        $this->line($sales, 0, 500);
        $this->line($settled, 100, 0);
        $this->line($settled, 0, 100);
    }

    private function account(string $code, string $name, ?int $parent, int $level, string $status): int
    {
        return DB::table('fn_accounts')->insertGetId(['name' => $name, 'code' => $code, 'level' => $level, 'type' => 1, 'status' => $status,
            'operation_type' => 1, 'parent_id' => $parent, 'created_by_id' => 1, 'created_at' => now(), 'updated_at' => now()]);
    }

    private function line(int $account, float $debit, float $credit): void
    {
        $constraint = DB::table('fn_constraints')->insertGetId(['code' => random_int(1000000, 9999999), 'type' => 1, 'date' => '2026-03-01', 'created_at' => now(), 'updated_at' => now()]
            + (Schema::hasColumn('fn_constraints', 'approval_status') ? ['approval_status' => 'approved'] : []));
        DB::table('fn_constraint_details')->insert(['constraint_id' => $constraint, 'account_id' => $account, 'date' => '2026-03-01', 'type' => $debit ? 'debtor' : 'creditor',
            'debtor_value' => $debit, 'creditor_value' => $credit]);
    }

    private function codes(array $input): array
    {
        $service = app(TrailBalanceService::class);
        $accounts = $service->getAccountSummaryForTrailBalance(2, '2026-01-01', '2026-12-31');

        return collect($service->applyFilters($accounts, $service->filtersFromRequest(new Request($input))))->pluck('code')->map(fn ($c) => (string) $c)->sort()->values()->all();
    }

    public function test_filters(): void
    {
        $this->assertSame(['11', '12', '41', '42'], $this->codes([]));
        $this->assertSame(['11', '41'], $this->codes(['balance_filter' => 'non_zero']));
        $this->assertSame(['12', '42'], $this->codes(['balance_filter' => 'zero']));
        $this->assertSame(['11'], $this->codes(['balance_filter' => 'debit']));
        $this->assertSame(['41'], $this->codes(['balance_filter' => 'credit']));
        $this->assertSame(['12'], $this->codes(['movement_filter' => 'without']));
        $this->assertSame(['41', '42'], $this->codes(['root_code' => '4']));
        $this->assertSame(['42'], $this->codes(['account_search' => 'مسدد']));
        $this->assertSame(['11', '12', '41', '42'], $this->codes(['balance_filter' => 'bogus', 'root_code' => 'x']));
    }

    public function test_screen_and_export_show_the_same_filtered_accounts(): void
    {
        $query = ['from_date' => '2026-01-01', 'to_date' => '2026-12-31', 'level' => 2, 'show_first_balance' => 0, 'balance_filter' => 'zero'];

        $html = $this->withHeaders(['X-Requested-With' => 'XMLHttpRequest'])->get(route('finance.trail-balance.index', $query))->assertOk()->json('data.html');
        // الحساب الذي لا رصيد له ولا حركة لا يظهر في الشاشة ولا في الشيت
        $this->assertStringNotContainsString('بنك فارغ', $html);
        $this->assertStringContainsString('إيراد مسدد', $html);
        $this->assertStringNotContainsString('صندوق اختبار', $html);

        $response = $this->get(route('finance.trail-balance.export', ['excel'] + $query))->assertOk();
        $content = method_exists($response->baseResponse, 'getFile') ? file_get_contents($response->baseResponse->getFile()->getPathname()) : $response->streamedContent();
        $file = tempnam(sys_get_temp_dir(), 'tb') . '.xlsx';
        file_put_contents($file, $content);
        $text = json_encode(\PhpOffice\PhpSpreadsheet\IOFactory::load($file)->getActiveSheet()->toArray(), JSON_UNESCAPED_UNICODE);
        @unlink($file);

        $this->assertStringNotContainsString('بنك فارغ', $text);
        $this->assertStringContainsString('إيراد مسدد', $text);
        $this->assertStringNotContainsString('صندوق اختبار', $text);
    }
}
