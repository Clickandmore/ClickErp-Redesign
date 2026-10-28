<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SuperAdminMcpSettingsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        config(['mcp.enabled' => false]);
    }

    private function asSuperAdmin(): self
    {
        return $this->withSession(['dev_superadmin_authenticated' => true]);
    }

    /** @test */
    public function the_screen_requires_the_superadmin_session(): void
    {
        $this->get(route('dev-superadmin.mcp.index'))->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.mcp.update'), [])->assertRedirect(route('superadmin.login'));
        $this->post(route('dev-superadmin.mcp.tokens.store'), [])->assertRedirect(route('superadmin.login'));
    }

    /** @test */
    public function saved_settings_override_the_env_defaults_and_switch_the_endpoint(): void
    {
        $admin = Admin::factory()->create();
        [, $plain] = McpAccessToken::issue($admin, 'test', 30);
        $ping = fn () => $this->withToken($plain)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping']);

        $ping()->assertNotFound();

        $this->asSuperAdmin()->get(route('dev-superadmin.mcp.index'))->assertOk()->assertSee('مغلق');

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.update'), [
            'enabled' => '1',
            'mask_sensitive' => '0',
            'rate_limit_per_minute' => 30,
            'token_days' => 14,
        ])->assertRedirect(route('dev-superadmin.mcp.index'));

        $this->assertSame(
            ['enabled' => true, 'rate_limit_per_minute' => 30, 'mask_sensitive' => false, 'token_days' => 14],
            McpSettings::all()
        );
        $ping()->assertOk();

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.update'), [
            'enabled' => '0',
            'mask_sensitive' => '1',
            'rate_limit_per_minute' => 30,
            'token_days' => 14,
        ]);
        $ping()->assertNotFound();
    }

    /** @test */
    public function issuing_shows_the_token_once_and_revoking_disables_it(): void
    {
        McpSettings::persist(['enabled' => true, 'mask_sensitive' => true, 'rate_limit_per_minute' => 60, 'token_days' => 7]);
        $admin = Admin::factory()->create();

        $response = $this->asSuperAdmin()->post(route('dev-superadmin.mcp.tokens.store'), [
            'admin_id' => $admin->id,
            'name' => 'Codex test',
        ]);
        $response->assertRedirect(route('dev-superadmin.mcp.index'));
        $plain = session('mcp_plain_token')['value'];

        $token = McpAccessToken::query()->where('admin_id', $admin->id)->firstOrFail();
        $this->assertSame(McpAccessToken::hash($plain), $token->token_hash);
        $this->assertSame(now()->addDays(7)->toDateString(), $token->expires_at->toDateString());

        // يظهر في الصفحة التي يُعاد التوجيه إليها، ثم يختفي.
        $this->get(route('dev-superadmin.mcp.index'))->assertOk()->assertSee($plain);
        $this->get(route('dev-superadmin.mcp.index'))
            ->assertOk()
            ->assertSee('Codex test')
            ->assertDontSee($plain);

        $this->withToken($plain)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertOk();

        $this->asSuperAdmin()->post(route('dev-superadmin.mcp.tokens.revoke', $token))
            ->assertRedirect(route('dev-superadmin.mcp.index'));
        $this->withToken($plain)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])->assertStatus(401);
    }

    /** @test */
    public function a_suspended_admin_cannot_receive_a_token(): void
    {
        $admin = Admin::factory()->suspended()->create();

        $this->asSuperAdmin()->post(route('dev-superadmin.mcp.tokens.store'), [
            'admin_id' => $admin->id,
            'name' => 'blocked',
        ])->assertSessionHasErrors('admin_id');

        $this->assertDatabaseMissing('mcp_access_tokens', ['admin_id' => $admin->id]);
    }
}
