<?php

namespace Tests\Feature\Mcp;

use App\Http\Controllers\McpOAuthController;
use App\Models\auth\Admin;
use App\Models\McpAccessToken;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * تدفق OAuth الذي يمر به claude.ai وChatGPT وClaude Code: اكتشاف، تسجيل عميل،
 * موافقة الموظف، استبدال الرمز بـ PKCE، ثم تجديد التوكن.
 */
class McpOAuthTest extends TestCase
{
    use DatabaseTransactions;

    private const REDIRECT = 'https://claude.ai/api/mcp/auth_callback';

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        $this->setOAuth(true);
    }

    private function setOAuth(bool $enabled): void
    {
        McpSettings::persist([
            'enabled' => true,
            'oauth_enabled' => $enabled,
            'mask_sensitive' => true,
            'rate_limit_per_minute' => 1000,
            'token_days' => 30,
        ]);
    }

    private function registerClient(array $overrides = []): array
    {
        return $this->postJson('/oauth/register', array_merge([
            'client_name' => 'Claude',
            'redirect_uris' => [self::REDIRECT],
            'token_endpoint_auth_method' => 'none',
        ], $overrides))->assertCreated()->json();
    }

    /** @return array{0: string, 1: string} verifier, challenge */
    private function pkce(): array
    {
        $verifier = Str::random(64);

        return [$verifier, rtrim(strtr(base64_encode(hash('sha256', $verifier, true)), '+/', '-_'), '=')];
    }

    private function authorizeParams(string $clientId, string $challenge, array $overrides = []): array
    {
        return array_merge([
            'response_type' => 'code',
            'client_id' => $clientId,
            'redirect_uri' => self::REDIRECT,
            'code_challenge' => $challenge,
            'code_challenge_method' => 'S256',
            'state' => 'xyz-state',
            'scope' => 'mcp',
            'resource' => url('/mcp'),
        ], $overrides);
    }

    /** يمر بشاشة الموافقة ويرجع رمز التفويض. */
    private function approve(Admin $admin, string $clientId, string $challenge): string
    {
        $response = $this->actingAs($admin, 'admin')
            ->post('/oauth/authorize', $this->authorizeParams($clientId, $challenge) + ['decision' => 'approve']);

        $location = $response->assertRedirect()->headers->get('Location');
        $this->assertStringStartsWith(self::REDIRECT . '?', $location);
        parse_str(parse_url($location, PHP_URL_QUERY), $query);
        $this->assertSame('xyz-state', $query['state']);

        return $query['code'];
    }

    private function exchange(string $clientId, string $code, string $verifier)
    {
        return $this->post('/oauth/token', [
            'grant_type' => 'authorization_code',
            'client_id' => $clientId,
            'code' => $code,
            'redirect_uri' => self::REDIRECT,
            'code_verifier' => $verifier,
        ]);
    }

    private function whoami(string $accessToken)
    {
        return $this->withToken($accessToken)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call', 'params' => ['name' => 'whoami'],
        ]);
    }

    /** @test */
    public function discovery_is_hidden_until_oauth_is_enabled(): void
    {
        $this->setOAuth(false);
        $this->getJson('/.well-known/oauth-protected-resource')->assertNotFound();
        $this->postJson('/oauth/register', [])->assertNotFound();
        $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])
            ->assertStatus(401)
            ->assertHeaderMissing('X-Never')
            ->assertHeader('WWW-Authenticate', 'Bearer realm="clickerp-mcp", error="invalid_token"');
    }

    /** @test */
    public function an_unauthenticated_mcp_call_points_to_the_discovery_documents(): void
    {
        $header = $this->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'ping'])
            ->assertStatus(401)
            ->headers->get('WWW-Authenticate');
        $this->assertStringContainsString('resource_metadata="' . url('/.well-known/oauth-protected-resource') . '"', $header);

        $this->getJson('/.well-known/oauth-protected-resource')
            ->assertOk()
            ->assertJsonPath('resource', url('/mcp'))
            ->assertJsonPath('authorization_servers.0', url('/'));

        $this->getJson('/.well-known/oauth-authorization-server')
            ->assertOk()
            ->assertJsonPath('issuer', url('/'))
            ->assertJsonPath('registration_endpoint', url('/oauth/register'))
            ->assertJsonPath('code_challenge_methods_supported', ['S256']);
    }

    /** @test */
    public function registration_only_accepts_known_or_loopback_redirects(): void
    {
        foreach (['https://evil.example.com/cb', 'http://claude.ai/cb', 'https://claude.ai.evil.com/cb', 'not a url'] as $bad) {
            $this->postJson('/oauth/register', ['redirect_uris' => [$bad]])
                ->assertStatus(400)
                ->assertJsonPath('error', 'invalid_redirect_uri');
        }

        $this->registerClient(['redirect_uris' => ['https://chatgpt.com/connector_platform_oauth_redirect']]);
        $this->registerClient(['redirect_uris' => ['http://localhost:53682/callback']]);

        $confidential = $this->registerClient(['token_endpoint_auth_method' => 'client_secret_post']);
        $this->assertNotEmpty($confidential['client_secret']);
    }

    /** @test */
    public function the_full_flow_gives_a_token_with_the_admins_identity(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->registerClient();
        [$verifier, $challenge] = $this->pkce();

        // بلا جلسة: يُحوَّل لتسجيل الدخول ويُحفظ رابط الرجوع.
        $this->get('/oauth/authorize?' . http_build_query($this->authorizeParams($client['client_id'], $challenge)))
            ->assertRedirect(route('admin.form.login'))
            ->assertSessionHas(McpOAuthController::RETURN_SESSION_KEY);

        $this->actingAs($admin, 'admin')
            ->get('/oauth/authorize?' . http_build_query($this->authorizeParams($client['client_id'], $challenge)))
            ->assertOk()
            ->assertSee('Claude')
            ->assertSee($admin->name);

        $code = $this->approve($admin, $client['client_id'], $challenge);
        Auth::guard('admin')->logout();

        $tokens = $this->exchange($client['client_id'], $code, $verifier)
            ->assertOk()
            ->assertJsonPath('token_type', 'Bearer')
            ->json();

        $this->whoami($tokens['access_token'])->assertJsonPath('result.structuredContent.id', $admin->id);

        // الرمز يُستخدم مرة واحدة فقط.
        $this->exchange($client['client_id'], $code, $verifier)->assertStatus(400)->assertJsonPath('error', 'invalid_grant');

        // التجديد يدوّر الـ refresh token ويلغي القديم.
        $refreshed = $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])->assertOk()->json();

        $this->whoami($tokens['access_token'])->assertStatus(401);
        $this->whoami($refreshed['access_token'])->assertJsonPath('result.structuredContent.id', $admin->id);
        $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(400);

        $this->assertDatabaseHas('mcp_audit_logs', ['admin_id' => $admin->id, 'method' => 'oauth/authorize', 'status' => 'ok']);
    }

    /** @test */
    public function pkce_and_client_binding_are_enforced(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->registerClient();
        $other = $this->registerClient();
        [$verifier, $challenge] = $this->pkce();

        $code = $this->approve($admin, $client['client_id'], $challenge);
        Auth::guard('admin')->logout();

        $this->exchange($client['client_id'], $code, Str::random(64))->assertStatus(400);
        $this->exchange($other['client_id'], $code, $verifier)->assertStatus(400);
        $this->exchange('cerp_client_unknown', $code, $verifier)->assertStatus(401);

        // المحاولات الفاشلة لا تستهلك الرمز.
        $this->exchange($client['client_id'], $code, $verifier)->assertOk();
    }

    /** @test */
    public function a_confidential_client_must_present_its_secret(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->registerClient(['token_endpoint_auth_method' => 'client_secret_basic']);
        [$verifier, $challenge] = $this->pkce();
        $code = $this->approve($admin, $client['client_id'], $challenge);
        Auth::guard('admin')->logout();

        $this->exchange($client['client_id'], $code, $verifier)->assertStatus(401);

        $this->withHeader('Authorization', 'Basic ' . base64_encode($client['client_id'] . ':' . $client['client_secret']))
            ->post('/oauth/token', [
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => self::REDIRECT,
                'code_verifier' => $verifier,
            ])->assertOk();
    }

    /** @test */
    public function denying_or_a_bad_request_never_issues_a_code(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->registerClient();
        [, $challenge] = $this->pkce();

        $location = $this->actingAs($admin, 'admin')
            ->post('/oauth/authorize', $this->authorizeParams($client['client_id'], $challenge) + ['decision' => 'deny'])
            ->headers->get('Location');
        $this->assertStringContainsString('error=access_denied', $location);
        $this->assertStringNotContainsString('code=', $location);

        // رابط رجوع غير مسجّل للعميل: صفحة خطأ، بلا أي توجيه إليه.
        $this->get('/oauth/authorize?' . http_build_query($this->authorizeParams($client['client_id'], $challenge, [
            'redirect_uri' => 'https://claude.ai/somewhere-else',
        ])))->assertStatus(400)->assertSee('طلب الربط غير صالح');

        // بلا PKCE: يرجع للعميل بخطأ.
        $this->get('/oauth/authorize?' . http_build_query($this->authorizeParams($client['client_id'], $challenge, [
            'code_challenge_method' => 'plain',
        ])))->assertRedirect()->assertRedirectContains('error=invalid_request');

        $this->assertDatabaseMissing('mcp_oauth_codes', ['admin_id' => $admin->id]);
    }

    /** @test */
    public function a_suspended_admin_loses_the_connection_on_refresh(): void
    {
        $admin = Admin::factory()->create();
        $client = $this->registerClient();
        [$verifier, $challenge] = $this->pkce();
        $code = $this->approve($admin, $client['client_id'], $challenge);
        Auth::guard('admin')->logout();
        $tokens = $this->exchange($client['client_id'], $code, $verifier)->json();

        $admin->update(['status' => 'suspended']);

        $this->whoami($tokens['access_token'])->assertStatus(401);
        $this->post('/oauth/token', [
            'grant_type' => 'refresh_token',
            'client_id' => $client['client_id'],
            'refresh_token' => $tokens['refresh_token'],
        ])->assertStatus(400);
    }

    /** @test */
    public function logging_in_returns_to_the_consent_screen(): void
    {
        $admin = Admin::factory()->create(['password' => bcrypt('secret-pass')]);
        $return = url('/oauth/authorize') . '?client_id=abc';

        $this->withSession([McpOAuthController::RETURN_SESSION_KEY => $return])
            ->postJson(route('admin.login'), ['login' => $admin->email, 'password' => 'secret-pass'])
            ->assertJsonPath('data.url', $return);
    }

    /** @test */
    public function the_login_return_cannot_be_used_as_an_open_redirect(): void
    {
        $admin = Admin::factory()->create(['password' => bcrypt('secret-pass')]);

        $url = $this->withSession([McpOAuthController::RETURN_SESSION_KEY => 'https://evil.example.com/'])
            ->postJson(route('admin.login'), ['login' => $admin->email, 'password' => 'secret-pass'])
            ->json('data.url');

        $this->assertNotSame('https://evil.example.com/', $url);
        $this->assertNull(McpAccessToken::query()->where('admin_id', $admin->id)->first());
    }
}
