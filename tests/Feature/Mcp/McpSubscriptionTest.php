<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\McpSubscriptionEvent;
use App\Models\Permission;
use App\PermKeys;
use App\Support\McpSettings;
use App\Support\McpSubscription;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

/** الخدمة مجانية أو مدفوعة: الحالات وحدودها، وكل مداخل الخدمة تحترمها. */
class McpSubscriptionTest extends TestCase
{
    use DatabaseTransactions;

    private Admin $admin;

    private McpAccessToken $record;

    private string $token;

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        McpSettings::persist(['enabled' => true, 'oauth_enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'human_confirmation' => false, 'access_mode' => 'selected']);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);
        [$this->record, $this->token] = McpAccessToken::issue($this->admin, 'test', 30);
        RateLimiter::clear('mcp-write:' . $this->admin->id);
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::CLIENTS_CREATE, PermKeys::MAIDS_EDIT);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function grant(string ...$permissions): void
    {
        foreach ($permissions as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                ['display_name' => $permission, 'group_name' => 'mcp_test', 'section_name' => 'mcp_test', 'is_active' => true]
            );
            $this->admin->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    /** مدفوع؛ الأرقام بالأيام نسبةً إلى اليوم. */
    private function paid(int $startOffset, int $endOffset, int $grace = 3): void
    {
        McpSettings::persist([
            'subscription_mode' => 'paid',
            'subscription_starts_at' => today()->addDays($startOffset)->toDateString(),
            'subscription_ends_at' => today()->addDays($endOffset)->toDateString(),
            'subscription_grace_days' => $grace,
        ]);
    }

    private function rpc(string $method, array $params = [])
    {
        return $this->withToken($this->token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => $method, 'params' => $params]);
    }

    private function tools(): array
    {
        return collect($this->rpc('tools/list')->assertOk()->json('result.tools'))->pluck('name')->all();
    }

    private function asSuperAdmin(): self
    {
        return $this->withSession(['dev_superadmin_authenticated' => true]);
    }

    /** @test */
    public function free_is_the_default_so_deploying_cuts_nobody_off(): void
    {
        $this->assertSame('free', McpSubscription::state());
        $this->assertTrue(McpSubscription::allowsWrite());
        $this->assertNull(McpSubscription::message());
        $this->assertContains('create_customer', $this->tools());
    }

    /** @test */
    public function each_state_starts_and_ends_on_the_right_day(): void
    {
        McpSettings::persist(['subscription_mode' => 'paid', 'subscription_starts_at' => '2030-03-01', 'subscription_ends_at' => '2030-03-31', 'subscription_grace_days' => 3]);

        foreach ([
            '2030-02-28 23:59:59' => 'scheduled',
            '2030-03-01 00:00:00' => 'active',
            '2030-03-31 23:59:59' => 'active',
            '2030-04-01 00:00:00' => 'grace',
            '2030-04-03 23:59:59' => 'grace',
            '2030-04-04 00:00:00' => 'expired',
        ] as $now => $state) {
            Carbon::setTestNow(Carbon::parse($now, 'Asia/Riyadh'));
            $this->assertSame($state, McpSubscription::state(), $now);
        }

        Carbon::setTestNow(Carbon::parse('2030-03-20', 'Asia/Riyadh'));
        $this->assertTrue(McpSubscription::expiringSoon());
        $this->assertSame(11, McpSubscription::daysLeft());

        Carbon::setTestNow(Carbon::parse('2030-03-10', 'Asia/Riyadh'));
        $this->assertFalse(McpSubscription::expiringSoon());

        // بلا سماح: تتوقف الخدمة في اليوم التالي مباشرة.
        McpSettings::persist(['subscription_grace_days' => 0]);
        Carbon::setTestNow(Carbon::parse('2030-04-01', 'Asia/Riyadh'));
        $this->assertSame('expired', McpSubscription::state());
    }

    /** @test */
    public function a_paid_subscription_without_an_end_date_closes_instead_of_running_free(): void
    {
        McpSettings::persist(['subscription_mode' => 'paid', 'subscription_starts_at' => null, 'subscription_ends_at' => null]);

        $this->assertSame('expired', McpSubscription::state());
    }

    /** @test */
    public function grace_keeps_reading_and_stops_writing_with_a_clear_reason(): void
    {
        $this->paid(-30, -1);

        $tools = $this->tools();
        $this->assertContains('whoami', $tools);
        $this->assertNotContains('create_customer', $tools);

        $call = $this->rpc('tools/call', ['name' => 'create_customer', 'arguments' => ['name' => 'س']])->assertOk()->json('result');
        $this->assertTrue($call['isError']);
        $this->assertStringContainsString('انتهى اشتراك', $call['content'][0]['text']);
        $this->assertDatabaseHas('mcp_audit_logs', ['admin_id' => $this->admin->id, 'tool' => 'create_customer', 'status' => 'expired']);

        $this->assertStringContainsString('انتهى اشتراك', $this->rpc('initialize', ['protocolVersion' => '2025-06-18'])->json('result.instructions'));
    }

    /** @test */
    public function expired_or_not_started_blocks_the_endpoint_and_is_logged_without_revoking(): void
    {
        foreach ([[-30, -10], [5, 30]] as [$start, $end]) {
            Cache::forget('mcp:blocked-audit:' . $this->record->id);
            $this->paid($start, $end);

            $response = $this->rpc('tools/list')->assertForbidden();
            $response->assertJsonPath('error', 'subscription_inactive');
            $this->assertNotEmpty($response->json('error_description'));
        }

        $this->assertDatabaseHas('mcp_audit_logs', ['admin_id' => $this->admin->id, 'status' => 'expired']);
        $this->assertNull($this->record->fresh()->revoked_at);
    }

    /** @test */
    public function renewing_restores_the_same_connection_at_once(): void
    {
        $this->paid(-30, -10);
        $this->rpc('tools/list')->assertForbidden();

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), [
            'subscription_mode' => 'paid',
            'subscription_starts_at' => today()->subDays(30)->toDateString(),
            'subscription_ends_at' => today()->addYear()->toDateString(),
            'subscription_grace_days' => 3,
        ])->assertRedirect(route('dev-superadmin.mcp.index', ['tab' => 'subscription']));

        $this->assertContains('create_customer', $this->tools());
    }

    /** @test */
    public function the_approval_page_and_upload_link_stop_with_writing(): void
    {
        McpSettings::persist(['human_confirmation' => true]);
        $preview = $this->rpc('tools/call', ['name' => 'create_customer', 'arguments' => [
            'name' => 'عميل معلق', 'phone' => '05' . random_int(10000000, 99999999), 'client_type' => 'person', 'national_num' => (string) random_int(1000000000, 1999999999),
        ]])->json('result.structuredContent');
        $this->assertArrayHasKey('confirm_url', $preview, json_encode($preview, JSON_UNESCAPED_UNICODE));

        $this->paid(-30, -1);
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'admin')->get($preview['confirm_url'])->assertForbidden();
        $this->actingAs($this->admin, 'admin')->post($preview['confirm_url'])->assertForbidden();
        $this->app['auth']->forgetGuards();

        $maidId = DB::table('maids')->insertGetId(['name' => 'عاملة', 'status' => 'available', 'created_at' => now(), 'updated_at' => now()]);
        $this->get(URL::temporarySignedRoute('mcp.maid-upload.show', now()->addMinutes(10), ['maid' => $maidId, 'admin' => $this->admin->id, 'token' => $this->record->id]))->assertForbidden();
    }

    /** @test */
    public function a_new_oauth_link_is_refused_while_stopped(): void
    {
        $client = \App\Models\McpOAuthClient::query()->create([
            'client_id' => 'cerp_client_' . uniqid(), 'client_name' => 'Claude',
            'redirect_uris' => ['https://claude.ai/callback'], 'token_endpoint_auth_method' => 'none',
        ]);
        $query = http_build_query([
            'response_type' => 'code', 'client_id' => $client->client_id, 'redirect_uri' => 'https://claude.ai/callback',
            'code_challenge' => str_repeat('a', 43), 'code_challenge_method' => 'S256', 'state' => 's',
        ]);

        $this->actingAs($this->admin, 'admin')->get('/oauth/authorize?' . $query)->assertOk();

        $this->paid(-30, -10);
        $this->actingAs($this->admin, 'admin')->get('/oauth/authorize?' . $query)->assertForbidden()->assertSee('انتهى اشتراك');
    }

    /** @test */
    public function the_screen_saves_mode_and_dates_and_keeps_a_history(): void
    {
        $this->asSuperAdmin()->get(route('dev-superadmin.mcp.index', ['tab' => 'subscription']))->assertOk()->assertSee('حفظ الاشتراك');

        // المدفوع يحتاج إلى تاريخين صحيحين.
        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), ['subscription_mode' => 'paid', 'subscription_grace_days' => 3])
            ->assertSessionHasErrors(['subscription_starts_at', 'subscription_ends_at']);
        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), [
            'subscription_mode' => 'paid', 'subscription_starts_at' => '2030-02-01', 'subscription_ends_at' => '2030-01-01', 'subscription_grace_days' => 3,
        ])->assertSessionHasErrors('subscription_ends_at');
        $this->assertSame('free', McpSubscription::state());

        $before = McpSubscriptionEvent::query()->count();
        $payload = [
            'subscription_mode' => 'paid', 'subscription_starts_at' => today()->toDateString(), 'subscription_ends_at' => today()->addMonth()->toDateString(),
            'subscription_grace_days' => 5, 'subscription_notes' => 'فاتورة 15', 'subscription_alert_admins' => [$this->admin->id],
        ];
        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), $payload)->assertRedirect();
        $this->assertSame('active', McpSubscription::state());
        $this->assertSame([(int) $this->admin->id], McpSettings::all()['subscription_alert_admins']);
        $this->assertSame($before + 1, McpSubscriptionEvent::query()->count());

        // حفظ بلا تغيير لا يضيف سطراً.
        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), $payload)->assertRedirect();
        $this->assertSame($before + 1, McpSubscriptionEvent::query()->count());

        $this->asSuperAdmin()->get(route('dev-superadmin.mcp.index', ['tab' => 'subscription']))->assertOk()->assertSee('فاتورة 15');

        // العودة للمجاني ترفع كل القيود.
        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.subscription.update'), ['subscription_mode' => 'free', 'subscription_grace_days' => 3])->assertRedirect();
        $this->assertSame('free', McpSubscription::state());
        $this->assertSame($before + 2, McpSubscriptionEvent::query()->count());
    }

    /** @test */
    public function saving_general_settings_does_not_touch_the_subscription(): void
    {
        $this->paid(-1, 30);
        $ends = McpSettings::all()['subscription_ends_at'];

        $this->asSuperAdmin()->put(route('dev-superadmin.mcp.update'), [
            'enabled' => '1', 'mask_sensitive' => '1', 'rate_limit_per_minute' => 1000, 'token_days' => 30, 'access_mode' => 'selected',
        ])->assertRedirect();

        $this->assertSame('paid', McpSettings::all()['subscription_mode']);
        $this->assertSame($ends, McpSettings::all()['subscription_ends_at']);
    }

    /** @test */
    public function the_guide_page_explains_the_subscription_state(): void
    {
        $this->paid(-30, 5);
        $this->actingAs($this->admin, 'admin')->get(route('mcp.guide'))->assertOk()->assertSee('ينتهي اشتراك المساعد الذكي')->assertSee('id="aiServerUrl"', false);

        $this->paid(-30, -1);
        $this->actingAs($this->admin, 'admin')->get(route('mcp.guide'))->assertOk()->assertSee('للقراءة فقط')->assertSee('id="aiServerUrl"', false);

        $this->paid(-30, -10);
        $this->actingAs($this->admin, 'admin')->get(route('mcp.guide'))->assertOk()->assertSee('انتهى اشتراك')->assertDontSee('id="aiServerUrl"', false);
    }

    /** @test */
    public function alerts_go_on_the_chosen_days_to_the_chosen_admins(): void
    {
        $count = fn () => DB::table('admin_notifications')->where('admin_id', $this->admin->id)->where('type', 'mcp_subscription')->count();
        McpSettings::persist(['subscription_alert_admins' => [$this->admin->id]]);

        $this->paid(-30, 10);
        Artisan::call('mcp:subscription-alerts');
        $this->assertSame(0, $count());

        $this->paid(-30, 3);
        Artisan::call('mcp:subscription-alerts');
        $this->assertSame(1, $count());

        $this->paid(-30, -1);
        Artisan::call('mcp:subscription-alerts');
        $this->assertSame(2, $count());

        // اليوم التالي لنهاية فترة السماح.
        $this->paid(-30, -4, 3);
        Artisan::call('mcp:subscription-alerts');
        $this->assertSame(3, $count());

        McpSettings::persist(['subscription_mode' => 'free']);
        Artisan::call('mcp:subscription-alerts');
        $this->assertSame(3, $count());
    }
}
