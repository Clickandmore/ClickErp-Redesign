<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpAuditLog;
use App\Models\McpOAuthClient;
use App\Models\Permission;
use App\PermKeys;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * تحكم Super Admin في MCP: من يستخدمه (الموظفون)، أي أداة (الأدوات)، والتنظيف،
 * مع بقاء صلاحيات الشاشات حداً أعلى لا يُتجاوز.
 */
class McpAccessControlTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        McpSettings::persist(['enabled' => true, 'oauth_enabled' => true, 'rate_limit_per_minute' => 1000]);
    }

    private function asSuperAdmin(): self
    {
        return $this->withSession(['dev_superadmin_authenticated' => true]);
    }

    private function token(Admin $admin): string
    {
        return McpAccessToken::issue($admin, 'test', 1)[1];
    }

    private function rpc(string $token, string $method, array $params = [])
    {
        return $this->withToken($token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    }

    private function tools(string $token): array
    {
        return collect($this->rpc($token, 'tools/list')->json('result.tools'))->pluck('name')->all();
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

    /** @test */
    public function selected_mode_only_lets_listed_and_enabled_admins_in(): void
    {
        $admin = Admin::factory()->create();
        $token = $this->token($admin);

        $this->rpc($token, 'ping')->assertStatus(403)->assertJsonPath('error', 'access_denied');
        $this->assertDatabaseHas('mcp_audit_logs', ['admin_id' => $admin->id, 'status' => 'blocked']);

        $access = McpAdminAccess::query()->create(['admin_id' => $admin->id]);
        $this->rpc($token, 'ping')->assertOk();

        $access->update(['enabled' => false]);
        $this->rpc($token, 'ping')->assertStatus(403);
    }

    /** @test */
    public function all_mode_lets_everyone_in_except_admins_switched_off(): void
    {
        McpSettings::persist(['access_mode' => McpSettings::ACCESS_ALL]);
        $admin = Admin::factory()->create();
        $token = $this->token($admin);

        $this->rpc($token, 'ping')->assertOk();

        McpAdminAccess::query()->create(['admin_id' => $admin->id, 'enabled' => false]);
        $this->rpc($token, 'ping')->assertStatus(403);
    }

    /** @test */
    public function a_tool_switched_off_disappears_and_refuses_calls(): void
    {
        $admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $admin->id]);
        $token = $this->token($admin);

        $this->assertContains('whoami', $this->tools($token));

        McpSettings::persist(['disabled_tools' => ['whoami']]);

        $this->assertNotContains('whoami', $this->tools($token));
        $this->rpc($token, 'tools/call', ['name' => 'whoami'])->assertOk()->assertJsonPath('result.isError', true);
        $this->assertDatabaseHas('mcp_audit_logs', ['admin_id' => $admin->id, 'tool' => 'whoami', 'status' => 'disabled']);
    }

    /** @test */
    public function per_admin_tools_narrow_the_list_but_never_exceed_screen_permissions(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::COMPLAINT_VIEW);
        $access = McpAdminAccess::query()->create(['admin_id' => $admin->id]);
        $token = $this->token($admin);

        $this->assertEqualsCanonicalizing(['whoami', 'list_complaints', 'get_complaint', 'needs_attention'], $this->tools($token));

        $access->update(['allowed_tools' => ['list_complaints']]);
        $this->assertSame(['list_complaints'], $this->tools($token));

        // اختيار أداة لا يملك الموظف صلاحية شاشتها لا يفتحها.
        $access->update(['allowed_tools' => ['list_complaints', 'invoices_summary']]);
        $this->assertSame(['list_complaints'], $this->tools($token));
        $this->rpc($token, 'tools/call', ['name' => 'invoices_summary'])->assertJsonPath('result.isError', true);
    }

    /** @test */
    public function oauth_consent_is_refused_for_an_admin_who_is_not_enabled(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->postJson('/oauth/register', [
            'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/api/mcp/auth_callback'],
            'token_endpoint_auth_method' => 'none',
        ])->json();
        $verifier = Str::random(64);
        $params = [
            'response_type' => 'code',
            'client_id' => $client['client_id'],
            'redirect_uri' => 'https://claude.ai/api/mcp/auth_callback',
            'code_challenge' => rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '='),
            'code_challenge_method' => 'S256',
            'state' => 's',
        ];

        $this->actingAs($admin, 'admin')->get('/oauth/authorize?' . http_build_query($params))
            ->assertStatus(403)
            ->assertSee('لم تفعّل الإدارة');
        $this->actingAs($admin, 'admin')->post('/oauth/authorize', $params + ['decision' => 'approve'])->assertStatus(403);
        $this->assertDatabaseMissing('mcp_oauth_codes', ['admin_id' => $admin->id]);

        McpAdminAccess::query()->create(['admin_id' => $admin->id]);
        $this->actingAs($admin, 'admin')->get('/oauth/authorize?' . http_build_query($params))->assertOk()->assertSee('موافقة وربط');
    }

    /** @test */
    public function changing_the_password_cuts_every_connection(): void
    {
        $admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $admin->id]);
        $token = $this->token($admin);
        $this->rpc($token, 'ping')->assertOk();

        $admin->update(['name' => 'تعديل لا يمس كلمة المرور']);
        $this->rpc($token, 'ping')->assertOk();

        $admin->update(['password' => Hash::make('a-new-password')]);
        $this->rpc($token, 'ping')->assertStatus(401);
    }

    /** @test */
    public function the_audit_log_masks_phone_and_passport_numbers(): void
    {
        $admin = Admin::factory()->create();
        $this->grant($admin, PermKeys::COMPLAINT_VIEW);
        McpAdminAccess::query()->create(['admin_id' => $admin->id]);

        $this->rpc($this->token($admin), 'tools/call', ['name' => 'list_complaints', 'arguments' => ['search' => '0551234567', 'status' => 'open']]);

        $log = McpAuditLog::query()->where('admin_id', $admin->id)->where('tool', 'list_complaints')->firstOrFail();
        $this->assertSame(['search' => '******4567', 'status' => 'open'], $log->arguments);

        $this->assertSame(
            ['passport_number' => '*****6789', 'nested' => ['phone' => '****', 'name' => 'Ali']],
            McpAuditLog::sanitizeArguments(['passport_number' => 'P12346789', 'nested' => ['phone' => '1235', 'name' => 'Ali']])
        );
    }

    /** @test */
    public function prune_removes_dead_tokens_and_old_logs_only(): void
    {
        McpSettings::persist(['audit_retention_days' => 30]);
        $admin = Admin::factory()->create();
        [$alive] = McpAccessToken::issue($admin, 'alive', 30);
        [$dead] = McpAccessToken::issue($admin, 'dead', 30);
        $dead->forceFill(['revoked_at' => now()->subDays(10)])->save();
        [$freshlyRevoked] = McpAccessToken::issue($admin, 'recent', 30);
        $freshlyRevoked->forceFill(['revoked_at' => now()->subDay()])->save();

        $old = McpAuditLog::query()->create(['admin_id' => $admin->id, 'method' => 'ping', 'status' => 'ok', 'created_at' => now()->subDays(40)]);
        $recent = McpAuditLog::query()->create(['admin_id' => $admin->id, 'method' => 'ping', 'status' => 'ok', 'created_at' => now()->subDays(5)]);

        $orphanClient = McpOAuthClient::query()->create(['client_id' => 'cerp_client_' . Str::random(10), 'redirect_uris' => ['https://claude.ai/cb']]);
        $orphanClient->forceFill(['created_at' => now()->subDays(8)])->save();

        $this->assertSame(0, Artisan::call('mcp:prune'));

        $this->assertDatabaseHas('mcp_access_tokens', ['id' => $alive->id]);
        $this->assertDatabaseHas('mcp_access_tokens', ['id' => $freshlyRevoked->id]);
        $this->assertDatabaseMissing('mcp_access_tokens', ['id' => $dead->id]);
        $this->assertDatabaseMissing('mcp_audit_logs', ['id' => $old->id]);
        $this->assertDatabaseHas('mcp_audit_logs', ['id' => $recent->id]);
        $this->assertDatabaseMissing('mcp_oauth_clients', ['id' => $orphanClient->id]);
    }

    /** @test */
    public function the_superadmin_screen_manages_employees_tools_and_apps(): void
    {
        $admin = Admin::factory()->create();

        $this->asSuperAdmin()->post(route('dev-superadmin.mcp.admins.store'), ['admin_id' => $admin->id, 'access_level' => 'read'])
            ->assertRedirect(route('dev-superadmin.mcp.index', ['tab' => 'employees']));
        $access = McpAdminAccess::query()->where('admin_id', $admin->id)->firstOrFail();

        $this->asSuperAdmin()->post(route('dev-superadmin.mcp.admins.store'), ['admin_id' => $admin->id, 'access_level' => 'read'])
            ->assertSessionHasErrors('admin_id');

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.admins.update', $access), [
            'enabled' => '1',
            'access_level' => 'write',
            'tools_mode' => 'selected',
            'allowed_tools' => ['whoami'],
        ])->assertRedirect();
        $access->refresh();
        $this->assertTrue($access->canWrite());
        $this->assertSame(['whoami'], $access->allowed_tools);

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.tools.update'), ['enabled_tools' => ['whoami'], 'write_enabled' => '1'])
            ->assertRedirect(route('dev-superadmin.mcp.index', ['tab' => 'tools']));
        $this->assertTrue(McpSettings::writeEnabled());
        $this->assertTrue(McpSettings::toolEnabled('whoami'));
        $this->assertFalse(McpSettings::toolEnabled('list_complaints'));
        // حفظ الأدوات لا يمس بقية الإعدادات.
        $this->assertTrue(McpSettings::enabled());

        $client = McpOAuthClient::query()->create(['client_id' => 'cerp_client_' . Str::random(10), 'client_name' => 'ChatGPT', 'redirect_uris' => ['https://chatgpt.com/cb']]);
        $token = McpAccessToken::query()->create(['admin_id' => $admin->id, 'oauth_client_id' => $client->id, 'name' => 'ChatGPT', 'token_hash' => hash('sha256', Str::random()), 'refresh_expires_at' => now()->addDay()]);
        $this->asSuperAdmin()->post(route('dev-superadmin.mcp.clients.revoke', $client))->assertRedirect();
        $this->assertNotNull($client->fresh()->revoked_at);
        $this->assertNotNull($token->fresh()->revoked_at);

        foreach (array_keys(\App\Http\Controllers\admin\SuperAdminMcpController::TABS) as $tab) {
            $this->asSuperAdmin()->get(route('dev-superadmin.mcp.index', ['tab' => $tab]))->assertOk();
        }
        $this->asSuperAdmin()->get(route('dev-superadmin.mcp.index', ['tab' => 'employees']))->assertSee($admin->name);
        $this->asSuperAdmin()->get(route('dev-superadmin.mcp.logs.export'))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $this->asSuperAdmin()->delete(route('dev-superadmin.mcp.admins.destroy', $access))->assertRedirect();
        $this->assertDatabaseMissing('mcp_admin_access', ['admin_id' => $admin->id]);
    }

    /** @test */
    public function the_new_screen_routes_require_the_superadmin_session(): void
    {
        $this->put(route('dev-superadmin.mcp.tools.update'))->assertRedirect(route('superadmin.login'));
        $this->post(route('dev-superadmin.mcp.admins.store'))->assertRedirect(route('superadmin.login'));
        $this->get(route('dev-superadmin.mcp.logs.export'))->assertRedirect(route('superadmin.login'));
    }
}
