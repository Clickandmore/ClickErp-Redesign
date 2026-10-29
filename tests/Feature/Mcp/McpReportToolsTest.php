<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\ingaz\Order;
use App\Models\McpAccessToken;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/** أدوات التقارير: كل أداة بصلاحية شاشتها، والأرقام والفترات محسوبة صحيحاً. */
class McpReportToolsTest extends TestCase
{
    use DatabaseTransactions;

    private const REPORTS = [
        'recruitment_contracts_report' => NewPermKeys::REC_CONTRACTS_VIEW,
        'rental_contracts_report' => PermKeys::RENT_CONTRACT_VIEW,
        'sponsorship_transfers_report' => PermKeys::SPONSOR_TRANSFER_VIEW,
        'shelter_report' => PermKeys::SHELTER_CONTRACT_VIEW,
        'orders_report' => PermKeys::ORDERS_VIEW,
        'maids_report' => PermKeys::MAIDS_VIEW,
        'customers_report' => PermKeys::CLIENTS_VIEW,
    ];

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        config(['mcp.enabled' => true, 'mcp.mask_sensitive' => true, 'mcp.access_mode' => 'all']);
    }

    private function grant(Admin $admin, string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['display_name' => $permission, 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
            );
            $admin->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function report(string $token, string $name, array $arguments = [])
    {
        return $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk();
    }

    private function tools(string $token): array
    {
        return collect($this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->json('result.tools'))->pluck('name')->all();
    }

    private function contract(string $status, string $date, array $extra = []): int
    {
        return DB::table('contracts')->insertGetId($extra + [
            'arabia_order_number' => random_int(900000000, 999999999),
            'status' => $status,
            'type' => 'recruitment',
            'contract_date' => $date,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function client(string $createdAt = null): int
    {
        return DB::table('users')->insertGetId([
            'name' => 'عميل تقرير',
            'phone_code' => '+966',
            'phone' => '5' . random_int(10000000, 99999999),
            'password' => bcrypt('password'),
            'register_from' => 'android',
            'created_at' => $createdAt ?? now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function each_report_follows_its_screen_permission_and_runs(): void
    {
        $admin = Admin::factory()->create();
        $token = McpAccessToken::issue($admin, 'test', 1)[1];

        foreach (array_keys(self::REPORTS) as $report) {
            $this->assertNotContains($report, $this->tools($token));
        }

        $this->grant($admin, ...array_values(self::REPORTS));
        $tools = $this->tools($token);

        foreach (array_keys(self::REPORTS) as $report) {
            $this->assertContains($report, $tools);
            $response = $this->report($token, $report, ['period' => 'all']);
            $this->assertFalse($response->json('result.isError'), $report . ': ' . $response->json('result.content.0.text'));
        }
    }

    /** @test */
    public function the_recruitment_report_counts_by_period_and_stage(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_VIEW);
        $token = McpAccessToken::issue($admin, 'test', 1)[1];

        $from = '2031-03-01';
        $this->contract('new', '2031-03-05');
        $this->contract('new', '2031-03-20');
        $this->contract('arrival', '2031-03-25', ['completed_at' => null]);
        $this->contract('new', '2031-04-02');

        $report = $this->report($token, 'recruitment_contracts_report', ['from' => $from, 'to' => '2031-03-31'])->json('result.structuredContent');

        $this->assertSame(3, $report['total']);
        $this->assertSame('custom', $report['period']);
        $byStatus = collect($report['by_status'])->keyBy('key');
        $this->assertSame(2, $byStatus['new']['count']);
        $this->assertSame(1, $byStatus['arrival']['count']);
        $this->assertNotSame('new', $byStatus['new']['label']);
        $this->assertSame(['2031-03' => 3], $report['monthly']);

        $onlyNew = $this->report($token, 'recruitment_contracts_report', ['from' => $from, 'to' => '2031-04-30', 'status' => 'new'])->json('result.structuredContent');
        $this->assertSame(3, $onlyNew['total']);
    }

    /** @test */
    public function the_orders_report_measures_conversion_to_contract(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::ORDERS_VIEW);
        $token = McpAccessToken::issue($admin, 'test', 1)[1];

        $make = fn (string $status) => Order::query()->create([
            'user_id' => $this->client(), 'job_type_id' => 1, 'status' => $status,
            'payment_method' => 'cash', 'order_code' => 'RPT-' . random_int(100000, 999999),
        ]);

        $converted = $make('under_process');
        $this->contract('new', now()->toDateString(), ['order_id' => $converted->id]);
        $make('new');
        $make('refused');
        $make('new');

        $report = $this->report($token, 'orders_report', ['period' => 'today'])->json('result.structuredContent');

        $this->assertSame(4, $report['total']);
        $this->assertSame(1, $report['effective']['converted_to_contract']);
        $this->assertSame(2, $report['effective']['open_without_contract']);
        $this->assertSame(1, $report['effective']['closed_without_contract']);
        $this->assertEquals(25.0, $report['conversion_rate_percent']);
    }

    /** @test */
    public function the_customers_report_counts_new_customers_by_source(): void
    {
        $admin = Admin::factory()->create();
        $token = McpAccessToken::issue($admin, 'test', 1)[1];

        $this->client('2031-05-02 10:00:00');
        $this->client('2031-05-20 10:00:00');
        $this->client('2031-06-01 10:00:00');
        $period = ['from' => '2031-05-01', 'to' => '2031-05-31'];

        // نفس عزل شاشة العملاء: موظف بلا فرع ولا «كل الفروع» لا يرى عملاء في وضع الفروع.
        $this->grant($admin, PermKeys::CLIENTS_VIEW);
        if (\App\Support\SystemModuleManager::isEnabled('branches')) {
            $this->assertSame(0, $this->report($token, 'customers_report', $period)->json('result.structuredContent.new_in_period.total'));
        }

        $this->grant($admin, PermKeys::CLIENTS_VIEW_ALL_BRANCHES);
        $report = $this->report($token, 'customers_report', $period)->json('result.structuredContent');

        $this->assertSame(2, $report['new_in_period']['total']);
        $this->assertSame(2, $report['new_in_period']['by_register_source']['android']);
    }

    /** @test */
    public function a_bad_date_is_a_tool_error_not_a_crash(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::MAIDS_VIEW);
        $token = McpAccessToken::issue($admin, 'test', 1)[1];

        $this->report($token, 'maids_report', ['from' => 'not-a-date'])
            ->assertJsonPath('result.isError', true);
    }
}
