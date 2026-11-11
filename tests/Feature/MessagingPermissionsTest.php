<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Permission;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\AutomationSettings;
use App\Support\CommunicationSettings;
use App\Support\NotificationAlertSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * تقسيم صلاحيات «الرسائل والتواصل»: الصلاحية القديمة notifications_control تبقى للتشغيل اليومي،
 * وإعدادات الربط (مفاتيح المزودين) تحتاج messaging_integrations_manage وحدها.
 */
class MessagingPermissionsTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::put('superadmin.system_updates', [
            'banner_enabled' => false, 'banner_days' => 30, 'sync_enabled' => false,
            'source_url' => '', 'has_source_key' => false, 'sync_interval_minutes' => 1,
        ]);
        Http::fake();
    }

    private function adminWith(string ...$permissions): Admin
    {
        $admin = Admin::factory()->global()->create();
        foreach ($permissions as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }

        return $admin;
    }

    private function alertsPayload(): array
    {
        $rules = NotificationAlertSettings::all();
        foreach ($rules as &$rule) {
            $rule['enabled'] = 0;
            $rule['template_id'] = null;
        }
        unset($rule);

        return ['section' => 'alerts', 'alerts' => $rules, 'custom_alerts' => []] + AutomationSettings::all();
    }

    public function test_legacy_notifications_control_keeps_daily_screens_but_not_integrations(): void
    {
        $this->actingAs($this->adminWith(PermKeys::NOTIFICATIONS_CONTROL), 'admin');

        $this->get(route('messages.index'))->assertOk();
        $this->get(route('notifications.settings.alerts'))->assertOk();

        $this->get(route('notifications.settings'))->assertForbidden();
        $this->post(route('notifications.settings.update'), ['section' => 'whatsapp', 'whatsapp' => ['driver' => 'meta']])
            ->assertForbidden();
        $this->post(route('notifications.settings.whatsapp.test'))->assertForbidden();
    }

    public function test_every_messaging_screen_renders_with_the_shared_navigation(): void
    {
        $this->actingAs($this->adminWith(PermKeys::NOTIFICATIONS_CONTROL), 'admin');

        foreach (['whatsapp.inbox.index', 'whatsapp.notifications.index', 'notifications.index', 'messages.index', 'admin.sms-templates.index', 'notifications.settings.alerts'] as $routeName) {
            $this->get(route($routeName))
                ->assertOk()
                ->assertSee('messaging-nav', false)
                // رابط إعدادات الربط لا يظهر لمن لا يملك صلاحيتها، لا في القائمة ولا في الصفحة.
                ->assertDontSee('href="' . route('notifications.settings') . '"', false);
        }
    }

    public function test_integrations_permission_opens_only_the_integration_settings(): void
    {
        $this->actingAs($this->adminWith(PermKeys::MESSAGING_INTEGRATIONS_MANAGE), 'admin');

        $this->get(route('notifications.settings'))->assertOk()->assertSee('إعدادات الربط مع مزودي الإرسال');
        $this->get(route('notifications.settings.alerts'))->assertForbidden();
        $this->get(route('messages.index'))->assertForbidden();
    }

    public function test_firebase_push_settings_belong_to_the_integration_settings(): void
    {
        $this->actingAs($this->adminWith(PermKeys::SETTINGS_VIEW, PermKeys::SETTINGS_CONTROL, PermKeys::NOTIFICATIONS_CONTROL), 'admin');
        $this->get(route('dash.firebase-settings.index'))->assertForbidden();
        $this->put(route('dash.firebase-settings.update'))->assertForbidden();

        $this->actingAs($this->adminWith(PermKeys::MESSAGING_INTEGRATIONS_MANAGE), 'admin');
        $this->get(route('dash.firebase-settings.index'))->assertOk()->assertSee('messaging-nav', false);
        $this->get(route('notifications.settings'))->assertOk()
            ->assertSee('href="' . route('dash.firebase-settings.index') . '"', false);
    }

    public function test_each_granular_permission_opens_its_own_screen(): void
    {
        $this->actingAs($this->adminWith(PermKeys::MESSAGING_LOG_VIEW), 'admin');
        $this->get(route('messages.index'))->assertOk();
        $this->get(route('notifications.index'))->assertForbidden();

        $this->actingAs($this->adminWith(PermKeys::MESSAGING_ALERTS_MANAGE), 'admin');
        $this->get(route('notifications.settings.alerts'))->assertOk();
        $this->get(route('notifications.settings'))->assertForbidden();
    }

    public function test_message_templates_are_no_longer_open_to_every_admin(): void
    {
        $this->actingAs($this->adminWith(), 'admin');
        $this->get(route('admin.sms-templates.index'))->assertForbidden();

        $this->assertNotEmpty(AdminRoutePermissionMatrix::resolve('admin.sms-templates.update', Request::create('/')));
    }

    public function test_settings_update_permission_follows_the_submitted_section(): void
    {
        $resolve = fn (string $section) => AdminRoutePermissionMatrix::resolve(
            'notifications.settings.update',
            Request::create('/', 'POST', ['section' => $section])
        );

        $this->assertSame([PermKeys::MESSAGING_INTEGRATIONS_MANAGE], $resolve('sms'));
        $this->assertSame([PermKeys::MESSAGING_INTEGRATIONS_MANAGE], $resolve('whatsapp'));
        $this->assertContains(PermKeys::NOTIFICATIONS_CONTROL, $resolve('alerts'));
        $this->assertNotContains(PermKeys::NOTIFICATIONS_CONTROL, $resolve('email'));
    }

    public function test_external_order_alert_is_saved_from_the_alerts_page_into_the_same_sms_key(): void
    {
        $this->actingAs($this->adminWith(PermKeys::MESSAGING_ALERTS_MANAGE), 'admin');

        $payload = $this->alertsPayload() + ['external_order_alert' => [
            'enabled' => 1, 'recipient_mode' => 'unified', 'unified_phone' => '0551234567',
        ]];

        $this->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');

        $stored = CommunicationSettings::sms()['external_order_admin_notification'];
        $this->assertTrue($stored['enabled']);
        $this->assertSame('unified', $stored['recipient_mode']);
        $this->assertSame('0551234567', $stored['unified_phone']);

        $this->get(route('notifications.settings.alerts'))->assertOk()->assertSee('external-order-alert-settings', false);
    }

    public function test_unified_phone_is_required_when_the_external_order_alert_targets_it(): void
    {
        $this->actingAs($this->adminWith(PermKeys::MESSAGING_ALERTS_MANAGE), 'admin');

        $payload = $this->alertsPayload() + ['external_order_alert' => [
            'enabled' => 1, 'recipient_mode' => 'unified', 'unified_phone' => '',
        ]];

        $this->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasErrors('external_order_alert.unified_phone');
    }

    public function test_saving_sms_integration_does_not_overwrite_the_external_order_alert(): void
    {
        CommunicationSettings::persist(['sms' => ['external_order_admin_notification' => [
            'enabled' => true, 'recipient_mode' => 'unified', 'unified_phone' => '966551234567',
        ]]], []);

        $this->actingAs($this->adminWith(PermKeys::MESSAGING_INTEGRATIONS_MANAGE), 'admin');

        $sms = CommunicationSettings::sms();
        $this->post(route('notifications.settings.update'), [
            'section' => 'sms',
            'sms' => array_merge($sms, [
                'provider' => 'msegat',
                'external_order_admin_notification' => ['enabled' => 0, 'recipient_mode' => 'selected_marketer'],
            ]),
        ])->assertSessionHasNoErrors();

        $stored = CommunicationSettings::sms()['external_order_admin_notification'];
        $this->assertTrue($stored['enabled']);
        $this->assertSame('966551234567', $stored['unified_phone']);
    }
}
