<?php

namespace Tests\Feature;

use App\Http\Actions\ingaz\PhoneVerificationAction;
use App\Models\auth\Admin;
use App\Models\ingaz\PhoneVerification;
use App\Models\Permission;
use App\Models\SmsTemplate;
use App\PermKeys;
use App\Services\VerificationCodeSender;
use App\Support\CommunicationSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Client\Request as HttpRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

/**
 * رمز التحقق عبر واتساب (Click Live Chat بقالب معتمد) بدل SMS، والرجوع إلى SMS، وسد ثغرة الرمز الثابت.
 */
class VerificationCodeChannelTest extends TestCase
{
    use DatabaseTransactions;

    private const PHONE = '0500000777';

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.system_updates', [
            'banner_enabled' => false, 'banner_days' => 30, 'sync_enabled' => false,
            'source_url' => '', 'has_source_key' => false, 'sync_interval_minutes' => 1,
        ]);
        config(['cache.default' => 'array']);
        RateLimiter::clear('clickchat-send:' . sha1('12|token'));
    }

    private function verificationTemplate(string $channel, ?array $binding = null): SmsTemplate
    {
        $template = SmsTemplate::query()->updateOrCreate(['name' => CommunicationSettings::VERIFICATION_TEMPLATE], [
            'title' => 'رمز التحقق',
            'message' => 'رمز التحقق الخاص بك هو {verification_code}',
            'channel' => $channel,
            'is_active' => true,
        ]);
        if (\Schema::hasColumn('sms_templates', 'clickchat_binding')) {
            $template->forceFill(['clickchat_binding' => $binding])->save();
        }

        return $template->fresh();
    }

    /** إعدادات SMS محفوظة (أو متوقفة) ثم إعداد Click Live Chat فوقها. */
    private function configure(bool $smsOn, bool $fallback = true, bool $clickchat = true): void
    {
        CommunicationSettings::persist([
            'sms' => [
                // تقنيات تمر عبر Http فيمكن تزييفها؛ Msegat يستخدم curl مباشرة.
                'provider' => 'taqnyat', 'gateway_status' => $smsOn ? 'on' : 'off', 'sender' => 'Brand',
                'taqnyat' => ['base_url' => 'https://sms.example.test', 'bearer_token' => 'sms-token'],
                'disable_phone_verification' => false,
            ],
            'verification' => ['fallback_to_sms' => $fallback],
        ], []);

        if ($clickchat) {
            config(['services.whatsapp.driver' => 'clickchat', 'services.whatsapp.default_country_code' => '966',
                'services.whatsapp.clickchat' => ['base_url' => 'https://chat.example.test', 'api_token' => '12|token',
                    'webhook_secret' => 'whsec_x', 'default_channel_account_id' => '3', 'branch_senders' => [],
                    'fallback' => 'none', 'auto_retry' => true, 'alert_admin_ids' => []],
                'services.whatsapp.opt_out' => ['enabled' => false, 'stop_keywords' => '', 'start_keywords' => '']]);
        }
    }

    private function fakeClickChat(bool $sendSucceeds = true): void
    {
        Http::fake([
            'chat.example.test/api/v1/channels*' => Http::response(['data' => [['id' => 3, 'type' => 'whatsapp_cloud', 'name' => 'Main', 'channel' => 'whatsapp']]]),
            'chat.example.test/api/v1/templates*' => Http::response(['data' => [
                ['name' => 'otp_code', 'language' => 'ar', 'category' => 'AUTHENTICATION', 'body' => 'رمزك {{1}}', 'variables' => 1],
            ]]),
            'chat.example.test/api/v1/messages/send' => $sendSucceeds
                ? Http::response(['message_id' => 9001, 'conversation_id' => 5, 'contact_id' => 7, 'channel_account_id' => 3, 'status' => 'sent'])
                : Http::response(['message' => 'Invalid recipient'], 422),
            'sms.example.test/*' => Http::response(['statusCode' => 201, 'messageId' => 55, 'message' => 'Success'], 201),
        ]);
    }

    private function sendCodeRequest(): Request
    {
        return Request::create('/', 'POST', ['phone' => self::PHONE, 'phone_code' => '966']);
    }

    public function test_switching_off_the_sms_gateway_no_longer_makes_every_code_12345(): void
    {
        $this->verificationTemplate(SmsTemplate::CHANNEL_SMS);
        $this->configure(smsOn: false, clickchat: false);
        Http::fake();

        $result = app(PhoneVerificationAction::class)->storeSendCode(['phone' => self::PHONE, 'phone_code' => '966'], $this->sendCodeRequest());

        $this->assertNotSame(CommunicationSettings::phoneVerificationBypassCode(), $result['code']);
        $this->assertMatchesRegularExpression('/^\d{5}$/', $result['code']);
        $this->assertSame($result['code'], PhoneVerification::where('phone', self::PHONE)->value('code'));
    }

    public function test_code_goes_through_the_approved_clickchat_template_when_whatsapp_is_chosen(): void
    {
        if (!\Schema::hasColumn('sms_templates', 'clickchat_binding')) {
            $this->markTestSkipped('clickchat_binding column missing in the test database.');
        }
        $this->verificationTemplate(SmsTemplate::CHANNEL_WHATSAPP, ['name' => 'otp_code', 'language' => 'ar', 'variables' => ['verification_code']]);
        $this->configure(smsOn: false);
        $this->fakeClickChat();

        $delivery = app(VerificationCodeSender::class)->send(self::PHONE, '48213', 'phone_verification_code');

        $this->assertTrue($delivery['success']);
        $this->assertSame('whatsapp', $delivery['channel']);
        $this->assertFalse($delivery['fallback_used']);
        Http::assertSent(fn (HttpRequest $r) => str_ends_with($r->url(), '/messages/send')
            && $r['template_name'] === 'otp_code'
            && $r['template_vars'] === ['48213']
            && $r['to'] === '+966500000777');
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'sms.example.test'));
    }

    public function test_failed_whatsapp_falls_back_to_sms_when_enabled(): void
    {
        $this->verificationTemplate(SmsTemplate::CHANNEL_WHATSAPP);
        $this->configure(smsOn: true, fallback: true);
        $this->fakeClickChat(sendSucceeds: false);

        $delivery = app(VerificationCodeSender::class)->send(self::PHONE, '11111', 'phone_verification_code');

        $this->assertTrue($delivery['fallback_used']);
        $this->assertSame('sms', $delivery['channel']);
        Http::assertSent(fn (HttpRequest $r) => str_contains($r->url(), 'sms.example.test'));
    }

    public function test_failed_whatsapp_does_not_touch_sms_when_fallback_is_off(): void
    {
        $this->verificationTemplate(SmsTemplate::CHANNEL_WHATSAPP);
        $this->configure(smsOn: true, fallback: false);
        $this->fakeClickChat(sendSucceeds: false);

        $delivery = app(VerificationCodeSender::class)->send(self::PHONE, '22222', 'phone_verification_code');

        $this->assertFalse($delivery['success']);
        $this->assertFalse($delivery['fallback_used']);
        Http::assertNotSent(fn (HttpRequest $r) => str_contains($r->url(), 'sms.example.test'));
    }

    public function test_integration_page_switches_the_verification_channel_and_shows_readiness(): void
    {
        $template = $this->verificationTemplate(SmsTemplate::CHANNEL_SMS);
        $this->configure(smsOn: true);
        $this->fakeClickChat();

        $admin = Admin::factory()->global()->create();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::MESSAGING_INTEGRATIONS_MANAGE, 'guard_name' => 'admin']));
        $this->actingAs($admin, 'admin');

        $this->get(route('notifications.settings'))->assertOk()
            ->assertSee('id="clickchat-readiness"', false)
            ->assertSee('id="verification-settings"', false);

        $this->post(route('notifications.settings.update'), [
            'section' => 'verification',
            'verification' => ['channel' => 'whatsapp', 'fallback_to_sms' => 0],
        ])->assertSessionHasNoErrors()->assertSessionHas('success');

        $this->assertSame(SmsTemplate::CHANNEL_WHATSAPP, $template->fresh()->channel);
        $this->assertSame('whatsapp', CommunicationSettings::verificationChannel());
        $this->assertFalse(CommunicationSettings::verification()['fallback_to_sms']);
    }

    public function test_whatsapp_cannot_be_chosen_before_a_provider_is_connected(): void
    {
        $this->verificationTemplate(SmsTemplate::CHANNEL_SMS);
        $this->configure(smsOn: true, clickchat: false);
        config(['services.whatsapp.driver' => 'clickchat', 'services.whatsapp.clickchat' => ['base_url' => '', 'api_token' => '']]);

        $admin = Admin::factory()->global()->create();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::MESSAGING_INTEGRATIONS_MANAGE, 'guard_name' => 'admin']));

        $this->actingAs($admin, 'admin')->post(route('notifications.settings.update'), [
            'section' => 'verification',
            'verification' => ['channel' => 'whatsapp', 'fallback_to_sms' => 1],
        ])->assertSessionHasErrors('verification.channel');
    }

    public function test_only_integration_managers_can_change_the_verification_channel(): void
    {
        $admin = Admin::factory()->global()->create();
        $admin->givePermissionTo(Permission::firstOrCreate(['name' => PermKeys::NOTIFICATIONS_CONTROL, 'guard_name' => 'admin']));

        $this->actingAs($admin, 'admin')->post(route('notifications.settings.update'), [
            'section' => 'verification',
            'verification' => ['channel' => 'whatsapp'],
        ])->assertForbidden();
    }
}
