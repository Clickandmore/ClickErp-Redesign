<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\Permission;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/** أدوات الموديولات: الإيجار، العملاء، الشكاوى، الفواتير، المالية، سجل الأنشطة. */
class McpModuleToolsTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        $this->setUpBranches();
        // هذه الاختبارات عن الأدوات لا عن قائمة الموظفين؛ McpAccessControlTest يغطي الوضع الافتراضي «المحددون فقط».
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

    private function token(Admin $admin): string
    {
        return McpAccessToken::issue($admin, 'test', 1)[1];
    }

    private function tools(string $token): array
    {
        return collect($this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])
            ->json('result.tools'))->pluck('name')->all();
    }

    private function callTool(string $token, string $name, array $arguments = [])
    {
        return $this->withToken($token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ]);
    }

    /** @test */
    public function each_module_tool_follows_its_screen_permission(): void
    {
        $admin = Admin::factory()->create();
        $token = $this->token($admin);

        $this->assertSame(['whoami'], $this->tools($token));

        $this->grant($admin, PermKeys::CLIENTS_VIEW, PermKeys::COMPLAINT_VIEW, PermKeys::ACTIVITY_VIEW, 'invoices_view', 'rent_contract_view');
        $tools = $this->tools($token);
        foreach (['search_customers', 'list_complaints', 'activity_log', 'invoices_summary', 'list_rental_contracts'] as $tool) {
            $this->assertContains($tool, $tools);
        }
        $this->assertNotContains('finance_account_balance', $tools);
        $this->assertNotContains('finance_bonds_summary', $tools);

        $this->callTool($token, 'finance_account_balance', ['account' => 'x'])->assertJsonPath('result.isError', true);
    }

    /** @test */
    public function finance_tools_use_the_finance_package_permissions(): void
    {
        $admin = Admin::factory()->create();
        $token = $this->token($admin);

        $this->grant($admin, 'finance_bonds_qabd_view');
        $tools = $this->tools($token);
        $this->assertContains('finance_bonds_summary', $tools);
        $this->assertNotContains('finance_account_balance', $tools);

        // صلاحية القبض فقط: سندات الصرف لا تُعرض.
        $bonds = $this->callTool($token, 'finance_bonds_summary')
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.not_permitted', ['سندات الصرف'])
            ->json('result.structuredContent');
        $this->assertArrayHasKey('qabd', $bonds);
        $this->assertArrayNotHasKey('sarf', $bonds);

        $this->grant($admin, 'finance_accounts_view');
        $this->assertContains('finance_account_balance', $this->tools($token));
    }

    /** @test */
    public function account_balance_follows_the_account_nature_and_includes_sub_accounts(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, 'finance_accounts_view');
        $code = random_int(800000000, 899999999);

        $parent = Account::query()->create(['name' => 'صندوق اختبار MCP', 'status' => 'debtor', 'level' => 1, 'code' => $code, 'type' => 1]);
        $child = Account::query()->create(['name' => 'صندوق فرعي', 'status' => 'debtor', 'level' => 2, 'code' => $code + 1, 'type' => 1, 'parent_id' => $parent->id]);

        $constraintId = DB::table('fn_constraints')->insertGetId([
            'type' => 'manual', 'value' => 500, 'date' => '2026-10-10', 'statement' => 'test',
            'approval_status' => 'approved', 'created_at' => now(), 'updated_at' => now(),
        ]);
        foreach ([[$parent->id, 300, 0], [$child->id, 200, 0], [$child->id, 0, 50]] as [$accountId, $debit, $credit]) {
            DB::table('fn_constraint_details')->insert([
                'constraint_id' => $constraintId, 'account_id' => $accountId, 'date' => '2026-10-10',
                'debtor_value' => $debit, 'creditor_value' => $credit, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->callTool($this->token($admin), 'finance_account_balance', ['account' => (string) $code, 'limit' => 1])
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.accounts.0.nature', 'مدين')
            ->assertJsonPath('result.structuredContent.accounts.0.sub_accounts', 1)
            ->assertJsonPath('result.structuredContent.accounts.0.debit', 500)
            ->assertJsonPath('result.structuredContent.accounts.0.credit', 50)
            ->assertJsonPath('result.structuredContent.accounts.0.balance', 450);
    }

    /** @test */
    public function customers_are_limited_to_the_admins_branch(): void
    {
        $this->enableBranchesModule();
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($own));
        $this->grant($admin, PermKeys::CLIENTS_VIEW);

        $tag = 'McpClient' . uniqid();
        foreach ([$own->id => 'own', $other->id => 'other'] as $branchId => $label) {
            DB::table('users')->insert([
                'name' => "{$tag} {$label}", 'phone' => '05' . random_int(10000000, 99999999),
                'branch_id' => $branchId, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $names = collect($this->callTool($this->token($admin), 'search_customers', ['query' => $tag])
            ->assertJsonPath('result.isError', false)
            ->json('result.structuredContent.customers'))->pluck('name')->all();

        $this->assertSame(["{$tag} own"], $names);
    }

    /** @test */
    public function the_activity_log_never_returns_secret_values(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::ACTIVITY_VIEW);
        $marker = 'mcp-activity-' . uniqid();

        DB::table('activity_log')->insert([
            'log_name' => 'default', 'description' => $marker, 'event' => 'updated',
            'subject_type' => Admin::class, 'subject_id' => $admin->id,
            'causer_type' => Admin::class, 'causer_id' => $admin->id,
            'properties' => json_encode([
                'attributes' => ['name' => 'جديد', 'password' => 'super-secret-hash'],
                'old' => ['name' => 'قديم', 'password' => 'old-secret-hash'],
            ]),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $response = $this->callTool($this->token($admin), 'activity_log', ['query' => $marker])
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.total', 1)
            ->assertJsonPath('result.structuredContent.latest.0.employee', $admin->name)
            ->assertJsonPath('result.structuredContent.latest.0.changes.name.old', 'قديم')
            ->assertJsonPath('result.structuredContent.latest.0.changes.password', '[مخفي]');

        $this->assertStringNotContainsString('secret-hash', $response->getContent());
    }

    /** @test */
    public function invoices_summary_totals_paid_and_unpaid(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, 'invoices_view');
        $day = '2031-01-15';

        foreach ([[1000, 150, true], [2000, 300, false]] as [$subtotal, $vat, $paid]) {
            DB::table('invoices')->insert([
                'subtotal' => $subtotal, 'discount' => 0, 'vat_amount' => $vat, 'total_due' => $subtotal + $vat,
                'is_paid' => $paid, 'created_at' => "{$day} 10:00:00", 'updated_at' => now(),
            ]);
        }

        $this->callTool($this->token($admin), 'invoices_summary', ['from' => $day, 'to' => $day])
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.totals.invoices', 2)
            ->assertJsonPath('result.structuredContent.totals.total_due', 3450)
            ->assertJsonPath('result.structuredContent.totals.paid', 1150)
            ->assertJsonPath('result.structuredContent.totals.unpaid', 2300)
            ->assertJsonPath('result.structuredContent.totals.unpaid_count', 1);
    }
}
