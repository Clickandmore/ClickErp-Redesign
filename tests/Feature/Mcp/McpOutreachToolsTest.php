<?php

namespace Tests\Feature\Mcp;

use App\Models\auth\Admin;
use App\Models\ingaz\Maid;
use App\Models\McpAccessToken;
use App\Models\McpAdminAccess;
use App\Models\office\Office;
use App\Models\Permission;
use App\PermKeys;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Support\CommunicationSettings;
use App\Support\McpSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * ظهور صفحة المساعد، وإنشاء الطلبات، ومراسلة العملاء. مسار الإرسال حقيقي حتى مزود الخدمة،
 * والمزود وحده مستبدل، فلا تخرج أي رسالة من الاختبار.
 */
class McpOutreachToolsTest extends TestCase
{
    use DatabaseTransactions;
    use \Tests\Concerns\InteractsWithBranches;

    private Admin $admin;

    private string $token;

    /** @var array<int, array{to: string, message: string}> */
    public array $sent = [];

    protected function setUp(): void
    {
        parent::setUp();
        DB::table('dev_superadmin_settings')->where('key', 'mcp_server')->delete();
        Cache::flush();
        Http::fake(['*' => Http::response(['code' => 1, 'message' => 'ok'])]);
        $this->enableBranchesModule(false);
        McpSettings::persist(['enabled' => true, 'rate_limit_per_minute' => 1000, 'write_enabled' => true, 'access_mode' => 'selected']);

        $this->admin = Admin::factory()->create();
        McpAdminAccess::query()->create(['admin_id' => $this->admin->id, 'access_level' => 'write']);
        $this->token = McpAccessToken::issue($this->admin, 'test', 1)[1];
        RateLimiter::clear('mcp-write:' . $this->admin->id);
        RateLimiter::clear('mcp-messages:' . $this->admin->id);
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

    private function tool(string $name, array $arguments = [])
    {
        return $this->withToken($this->token)->postJson('/mcp', [
            'jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/call',
            'params' => ['name' => $name, 'arguments' => $arguments],
        ])->assertOk()->json('result');
    }

    private function approve(string $url)
    {
        $this->app['auth']->forgetGuards();

        return $this->actingAs($this->admin, 'admin')->post($url)->assertOk();
    }

    private function customer(array $extra = []): int
    {
        return DB::table('users')->insertGetId($extra + [
            'name' => 'عميل ' . uniqid(), 'phone_code' => '+966', 'phone' => '5' . random_int(10000000, 99999999),
            'is_blocked' => 0, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    private function fakeWhatsApp(): void
    {
        $test = $this;
        $this->app->instance(WhatsAppMessagingService::class, new class($test) extends WhatsAppMessagingService {
            public function __construct(private $test)
            {
            }

            public function driver(): string
            {
                return 'meta';
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function defaultSenderKey(): ?string
            {
                return null;
            }

            public function sendText(string $to, string $message, array $context = []): array
            {
                $this->test->sent[] = ['to' => $to, 'message' => $message];

                return ['success' => !str_contains($message, 'فشل-المزود'), 'response' => ['message' => 'رفض المزود']];
            }
        });
    }

    private function maid(array $extra = [], array $jobTypes = [1, 2, 3]): Maid
    {
        $nationality = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religion = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $maid = Maid::create($extra + [
            'name' => 'عاملة', 'passport_num' => 'ORD-' . uniqid(),
            'nationality_id' => $nationality, 'religion_id' => $religion,
            'age' => 30, 'height' => '', 'weight' => '', 'cv_file' => '', 'image' => '',
            'office_id' => Office::create(['nationality_id' => $nationality, 'status' => 'active'])->id,
            'status' => 'available', 'available' => true, 'is_show' => true, 'maid_accommodation_type' => 1,
        ]);
        // قاعدة الاختبار لا تنسخ أنواع الخدمة.
        foreach ($jobTypes as $jobType) {
            DB::table('job_types')->insertOrIgnore(['id' => $jobType, 'created_at' => now(), 'updated_at' => now()]);
        }
        $maid->jobTypes()->sync($jobTypes);

        return $maid;
    }

    // ---------------------------------------------------------------- صفحة المساعد

    /** @test */
    public function the_superadmin_controls_the_guide_page_and_it_sits_inside_system_settings(): void
    {
        $this->grant(PermKeys::SETTINGS_VIEW);
        $link = route('mcp.guide');

        $this->actingAs($this->admin, 'admin')->get($link)->assertOk()
            ->assertSeeInOrder(['id="settingsOperation"', $link . '"'], false);

        // بلا صلاحيات إعدادات: الرابط يصل داخل القسم، ولا تنكشف روابط الإعدادات.
        $plain = Admin::factory()->create();
        $this->actingAs($plain, 'admin')->get($link)->assertOk()
            ->assertSeeInOrder(['id="settingsOperation"', $link . '"'], false)
            ->assertDontSee(route('settings.section', 'dashboard') . '"', false);
        $this->app['auth']->forgetGuards();

        $this->withSession(['dev_superadmin_authenticated' => true])->put(route('dev-superadmin.mcp.update'), [
            'enabled' => '1', 'mask_sensitive' => '1', 'rate_limit_per_minute' => 1000, 'token_days' => 30,
            'access_mode' => 'selected', 'guide_page_enabled' => '0',
        ])->assertRedirect();

        $this->assertFalse(McpSettings::guidePageEnabled());
        $this->actingAs($this->admin, 'admin')->get($link)->assertNotFound();
        $this->actingAs($this->admin, 'admin')->get(url('admin/no-permissions'))->assertOk()->assertDontSee($link, false);
        $this->actingAs($plain, 'admin')->get(url('admin/no-permissions'))->assertOk()->assertDontSee($link, false);

        // الإخفاء لا يوقف المساعد نفسه.
        $this->assertFalse($this->tool('whoami')['isError']);
    }

    // ---------------------------------------------------------------- الرسائل

    /** @test */
    public function messages_always_need_the_employees_own_approval(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::NOTIFICATIONS_CONTROL);
        $this->fakeWhatsApp();
        // حتى مع إيقاف الموافقة العامة، الرسائل لا تُرسل بتأكيد من النموذج.
        McpSettings::persist(['human_confirmation' => false]);

        $id = $this->customer(['name' => 'سارة']);
        $preview = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$id], 'message' => 'مرحباً {client_name}'])['structuredContent'];

        $this->assertSame('awaiting_approval', $preview['status']);
        $this->assertSame('مرحباً سارة', $preview['preview']['message_sample']);
        $this->assertSame([], $this->sent);

        $this->assertTrue($this->tool('send_customer_message', ['confirmation_token' => $preview['confirmation_token']])['isError']);
        $this->assertSame([], $this->sent);

        $this->app['auth']->forgetGuards();
        $this->post($preview['confirm_url'])->assertForbidden();
        $this->assertSame([], $this->sent);
    }

    /** @test */
    public function one_approval_sends_a_personal_message_to_each_customer_once(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::NOTIFICATIONS_CONTROL);
        $this->fakeWhatsApp();
        $first = $this->customer(['name' => 'سارة']);
        $second = $this->customer(['name' => 'ليلى']);
        $blocked = $this->customer(['name' => 'محظور', 'is_blocked' => 1]);

        $preview = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$first, $second, $blocked], 'message' => 'مرحباً {client_name}'])['structuredContent'];
        $this->assertSame(2, $preview['preview']['recipients_count']);
        $this->assertArrayHasKey('not_sendable', $preview['preview']);

        $this->approve($preview['confirm_url'])->assertSee('قبل مزود الخدمة 2 من 2');
        $this->assertEqualsCanonicalizing(['مرحباً سارة', 'مرحباً ليلى'], array_column($this->sent, 'message'));

        // إعادة الفتح لا تكرر الإرسال، والنموذج يعرف النتيجة.
        $this->app['auth']->forgetGuards();
        $this->actingAs($this->admin, 'admin')->post($preview['confirm_url'])->assertNotFound();
        $this->assertCount(2, $this->sent);
        $this->assertSame(2, $this->tool('send_customer_message', ['confirmation_token' => $preview['confirmation_token']])['structuredContent']['sent']);
    }

    /** @test */
    public function a_provider_failure_is_reported_and_does_not_stop_the_others(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::NOTIFICATIONS_CONTROL);
        $this->fakeWhatsApp();
        $ids = [$this->customer(['name' => 'فشل-المزود']), $this->customer(['name' => 'ليلى'])];

        $preview = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => $ids, 'message' => 'مرحباً {client_name}'])['structuredContent'];
        $this->approve($preview['confirm_url'])->assertSee('قبل مزود الخدمة 1 من 2')->assertSee('فشل-المزود');
        $this->assertCount(2, $this->sent);
    }

    /** @test */
    public function sms_goes_through_the_message_screen_gateway(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::NOTIFICATIONS_CONTROL);
        CommunicationSettings::persistSection('sms', [
            'gateway_status' => 'on', 'provider' => 'taqnyat', 'sender' => 'ClickERP',
            'taqnyat' => ['base_url' => 'https://sms.test', 'bearer_token' => 'k'],
        ]);

        $id = $this->customer(['name' => 'سارة']);
        $preview = $this->tool('send_customer_message', ['channel' => 'sms', 'customer_ids' => [$id], 'message' => 'تذكير {client_name}']);
        $this->assertFalse($preview['isError'], json_encode($preview, JSON_UNESCAPED_UNICODE));

        $this->approve($preview['structuredContent']['confirm_url'])->assertSee('قبل مزود الخدمة 1 من 1');
        Http::assertSent(fn ($request) => str_contains((string) $request->url(), 'sms.test') && str_contains(json_encode($request->data(), JSON_UNESCAPED_UNICODE), 'تذكير سارة'));
    }

    /** @test */
    public function message_limits_and_channel_setup_are_enforced(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW, PermKeys::NOTIFICATIONS_CONTROL);
        $this->fakeWhatsApp();
        McpSettings::persist(['message_max_recipients' => 2, 'messages_per_hour' => 3]);
        $ids = [$this->customer(), $this->customer(), $this->customer()];

        $tooMany = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => $ids, 'message' => 'نص']);
        $this->assertTrue($tooMany['isError']);

        $unknownVariable = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$ids[0]], 'message' => 'عقدك {contract_number}']);
        $this->assertTrue($unknownVariable['isError']);

        $preview = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$ids[0], $ids[1]], 'message' => 'نص'])['structuredContent'];
        $this->approve($preview['confirm_url']);

        $overHour = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$ids[1], $ids[2]], 'message' => 'نص']);
        $this->assertTrue($overHour['isError']);
        $this->assertStringContainsString('في الساعة', $overHour['content'][0]['text']);
    }

    /** @test */
    public function messages_need_their_permission_and_stay_inside_the_branch(): void
    {
        $this->grant(PermKeys::CLIENTS_VIEW);
        $names = fn () => collect($this->withToken($this->token)->postJson('/mcp', ['jsonrpc' => '2.0', 'id' => 1, 'method' => 'tools/list'])->json('result.tools'))->pluck('name')->all();
        $this->assertNotContains('send_customer_message', $names());

        $this->grant(PermKeys::NOTIFICATIONS_CONTROL);
        $this->assertContains('send_customer_message', $names());

        $this->fakeWhatsApp();
        $this->enableBranchesModule(true);
        Cache::flush();
        $mine = \App\Models\Branch::factory()->create();
        $other = \App\Models\Branch::factory()->create();
        $this->admin->forceFill(['branch_id' => $mine->id])->saveQuietly();
        $outsider = $this->customer(['branch_id' => $other->id]);

        $result = $this->tool('send_customer_message', ['channel' => 'whatsapp', 'customer_ids' => [$outsider], 'message' => 'نص']);
        $this->assertTrue($result['isError'], json_encode($result, JSON_UNESCAPED_UNICODE));
        $this->assertSame([], $this->sent);
    }

    // ---------------------------------------------------------------- الطلبات

    /** @test */
    public function an_approved_order_is_created_and_reserves_the_maid(): void
    {
        $this->grant(PermKeys::ORDERS_CREATE, PermKeys::ORDERS_VIEW);
        $maid = $this->maid();
        $customer = $this->customer();

        $preview = $this->tool('create_order', ['customer_id' => $customer, 'maid_id' => $maid->id, 'order_type' => 'recruitment']);
        $this->assertFalse($preview['isError'], json_encode($preview, JSON_UNESCAPED_UNICODE));
        $this->assertSame(0, DB::table('orders')->where('user_id', $customer)->count());

        $this->approve($preview['structuredContent']['confirm_url'])->assertSee('تم التنفيذ');

        $order = DB::table('orders')->where('user_id', $customer)->first();
        $this->assertNotNull($order);
        $this->assertSame((int) $maid->id, (int) $order->maid_id);
        $this->assertSame(1, (int) $order->job_type_id);
        $this->assertSame('new', $order->status);
        $this->assertSame((int) $this->admin->id, (int) $order->customer_id);
        $this->assertSame((int) $this->admin->id, (int) $order->created_by_admin_id);
        $this->assertSame('reserved', $maid->fresh()->status);

        // العاملة لم تعد متاحة لطلب آخر.
        $again = $this->tool('create_order', ['customer_id' => $this->customer(), 'maid_id' => $maid->id, 'order_type' => 'recruitment']);
        $this->assertTrue($again['isError']);
    }

    /** @test */
    public function a_maid_taken_after_the_preview_is_not_double_booked(): void
    {
        $this->grant(PermKeys::ORDERS_CREATE);
        $maid = $this->maid();
        $customer = $this->customer();

        $preview = $this->tool('create_order', ['customer_id' => $customer, 'maid_id' => $maid->id, 'order_type' => 'recruitment'])['structuredContent'];
        DB::table('maids')->where('id', $maid->id)->update(['status' => 'reserved', 'available' => false]);

        $this->approve($preview['confirm_url'])->assertSee('لم يُنفذ الطلب');
        $this->assertSame(0, DB::table('orders')->where('user_id', $customer)->count());
    }

    /** @test */
    public function order_rules_match_the_booking_flow(): void
    {
        $this->assertTrue($this->tool('create_order', [])['isError']);
        $this->grant(PermKeys::ORDERS_CREATE);
        $customer = $this->customer();

        $wrongType = $this->tool('create_order', ['customer_id' => $customer, 'maid_id' => $this->maid([], [1])->id, 'order_type' => 'rental', 'package_id' => 1]);
        $this->assertTrue($wrongType['isError']);

        $noPackage = $this->tool('create_order', ['customer_id' => $customer, 'maid_id' => $this->maid()->id, 'order_type' => 'rental']);
        $this->assertTrue($noPackage['isError']);

        $blocked = $this->tool('create_order', ['customer_id' => $this->customer(['is_blocked' => 1]), 'maid_id' => $this->maid()->id, 'order_type' => 'recruitment']);
        $this->assertTrue($blocked['isError']);

        $options = $this->tool('form_options', ['form' => 'order']);
        $this->assertFalse($options['isError'], json_encode($options, JSON_UNESCAPED_UNICODE));
        $this->assertArrayHasKey('rental_packages', $options['structuredContent']);
    }
}
