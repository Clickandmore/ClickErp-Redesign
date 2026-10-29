<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\McpAuditLog;
use App\Models\Permission;
use App\NewPermKeys;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class McpServerTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        // إعداد محفوظ من شاشة Super Admin يتقدم على config، فيُزال ليحكم الاختبار وحده.
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        $this->setUpBranches();
        // هذه الاختبارات عن الأدوات لا عن قائمة الموظفين؛ McpAccessControlTest يغطي الوضع الافتراضي «المحددون فقط».
        config(['mcp.enabled' => true, 'mcp.mask_sensitive' => true, 'mcp.access_mode' => 'all']);
    }

    private function grant(Admin $admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            ['display_name' => $permission, 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
        );

        $admin->givePermissionTo($permission);
    }

    private function tokenFor(Admin $admin, ?int $days = 30): string
    {
        return McpAccessToken::issue($admin, 'test', $days)[1];
    }

    private function rpc(string $token, string $method, array $params = [], $id = 1)
    {
        return $this->withToken($token)->postJson('/mcp', array_filter([
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ], fn ($value) => $value !== null));
    }

    private function callTool(string $token, string $name, array $arguments = [])
    {
        return $this->rpc($token, 'tools/call', ['name' => $name, 'arguments' => $arguments]);
    }

    private function makeContract(?int $branchId, int $number): int
    {
        return DB::table('contracts')->insertGetId([
            'branch_id' => $branchId,
            'arabia_order_number' => $number,
            'status' => 'new',
            'type' => 'recruitment',
            'contract_date' => now()->toDateString(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /** @test */
    public function the_endpoint_is_hidden_until_enabled(): void
    {
        config(['mcp.enabled' => false]);
        $token = $this->tokenFor(Admin::factory()->create());

        $this->rpc($token, 'ping')->assertNotFound();
    }

    /** @test */
    public function missing_revoked_expired_or_suspended_tokens_are_rejected(): void
    {
        $admin = Admin::factory()->create();

        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
        $this->rpc('cerp_mcp_not-a-real-token', 'ping')->assertStatus(401);

        [$revoked, $revokedPlain] = McpAccessToken::issue($admin, 'revoked');
        $revoked->update(['revoked_at' => now()]);
        $this->rpc($revokedPlain, 'ping')->assertStatus(401);

        [$expired, $expiredPlain] = McpAccessToken::issue($admin, 'expired', 1);
        $expired->update(['expires_at' => now()->subDay()]);
        $this->rpc($expiredPlain, 'ping')->assertStatus(401);

        $suspended = Admin::factory()->suspended()->create();
        $this->rpc($this->tokenFor($suspended), 'ping')->assertStatus(401);
    }

    /** @test */
    public function initialize_negotiates_the_protocol_and_notifications_get_no_body(): void
    {
        $token = $this->tokenFor(Admin::factory()->create());

        $this->rpc($token, 'initialize', ['protocolVersion' => '2025-03-26', 'capabilities' => new \stdClass()])
            ->assertOk()
            ->assertJsonPath('result.protocolVersion', '2025-03-26')
            ->assertJsonPath('result.serverInfo.name', 'clickerp');

        $this->rpc($token, 'initialize', ['protocolVersion' => '1999-01-01'])
            ->assertJsonPath('result.protocolVersion', '2025-06-18');

        $this->withToken($token)
            ->postJson('/mcp', ['jsonrpc' => '2.0', 'method' => 'notifications/initialized'])
            ->assertStatus(202);

        $this->rpc($token, 'resources/list')->assertJsonPath('error.code', -32601);
        $this->withToken($token)->get('/mcp')->assertStatus(405);
    }

    /** @test */
    public function tools_follow_the_admin_screen_permissions(): void
    {
        $admin = Admin::factory()->create();
        $token = $this->tokenFor($admin);

        $names = collect($this->rpc($token, 'tools/list')->json('result.tools'))->pluck('name');
        $this->assertSame(['whoami'], $names->all());

        $this->callTool($token, 'list_recruitment_contracts')
            ->assertOk()
            ->assertJsonPath('result.isError', true);
        $this->assertDatabaseHas('mcp_audit_logs', [
            'admin_id' => $admin->id,
            'tool' => 'list_recruitment_contracts',
            'status' => 'denied',
        ]);

        $this->grant($admin, NewPermKeys::REC_CONTRACTS_VIEW);
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $names = collect($this->rpc($token, 'tools/list')->json('result.tools'))->pluck('name');
        $this->assertContains('list_recruitment_contracts', $names);
        $this->assertContains('get_recruitment_contract', $names);
        $this->assertNotContains('search_maids', $names);
    }

    /** @test */
    public function contract_tools_only_see_the_admins_branch(): void
    {
        $this->enableBranchesModule();
        $this->makeBranch(['is_main' => true]);
        $own = $this->makeBranch();
        $other = $this->makeBranch();
        $admin = $this->lockToOwnBranch($this->branchAdmin($own));
        $this->grant($admin, NewPermKeys::REC_CONTRACTS_VIEW);
        $token = $this->tokenFor($admin);

        $ownNumber = random_int(900000000, 949999999);
        $otherNumber = random_int(950000000, 999999999);
        $this->makeContract($own->id, $ownNumber);
        $otherId = $this->makeContract($other->id, $otherNumber);

        $listed = collect($this->callTool($token, 'list_recruitment_contracts', ['limit' => 50])
            ->assertJsonPath('result.isError', false)
            ->json('result.structuredContent.contracts'))
            ->pluck('contract_number');
        $this->assertContains($ownNumber, $listed);
        $this->assertNotContains($otherNumber, $listed);

        $this->callTool($token, 'get_recruitment_contract', ['contract_number' => $ownNumber])
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.contract_number', $ownNumber);

        $this->callTool($token, 'get_recruitment_contract', ['id' => $otherId])
            ->assertJsonPath('result.isError', true);

        // نص غير رقمي لا يجوز أن يطابق أرقام العقود (MySQL يحوّله إلى 0).
        $this->callTool($token, 'list_recruitment_contracts', ['query' => 'no-such-client-xyz'])
            ->assertJsonPath('result.structuredContent.total', 0);
    }

    /** @test */
    public function search_maids_masks_passport_numbers(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::MAIDS_VIEW);
        $name = 'McpMaid' . uniqid();
        DB::table('maids')->insert([
            'name' => $name,
            'passport_num' => 'P12345678',
            'status' => 'available',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->callTool($this->tokenFor($admin), 'search_maids', ['query' => $name])
            ->assertJsonPath('result.isError', false)
            ->assertJsonPath('result.structuredContent.total', 1)
            ->assertJsonPath('result.structuredContent.maids.0.passport_number', '*****5678');
    }

    /** @test */
    public function the_token_command_issues_a_working_token_and_revokes_it(): void
    {
        $admin = Admin::factory()->create();

        Artisan::call('mcp:token', ['action' => 'issue', '--admin' => $admin->email, '--name' => 'cli']);
        preg_match('/cerp_mcp_\S+/', Artisan::output(), $match);
        $this->assertNotEmpty($match);

        $this->rpc($match[0], 'tools/call', ['name' => 'whoami'])
            ->assertJsonPath('result.structuredContent.id', $admin->id);

        $token = McpAccessToken::query()->where('admin_id', $admin->id)->firstOrFail();
        $this->assertNotSame($match[0], $token->token_hash);

        Artisan::call('mcp:token', ['action' => 'revoke', '--id' => $token->id]);
        $this->rpc($match[0], 'ping')->assertStatus(401);
        $this->assertSame(1, McpAuditLog::query()->where('admin_id', $admin->id)->where('status', 'ok')->count());
    }
}
