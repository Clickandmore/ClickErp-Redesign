<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Permission;
use App\Support\AutomationSettings;
use App\Support\NotificationAlertSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class NotificationAlertSettingsPersistenceTest extends TestCase
{
    use DatabaseTransactions;

    protected function setUp(): void
    {
        parent::setUp();
        // Rendering the admin layout must not pull the unrelated system-updates feed.
        \Illuminate\Support\Facades\Cache::put('superadmin.system_updates', [
            'banner_enabled' => false, 'banner_days' => 30, 'sync_enabled' => false,
            'source_url' => '', 'has_source_key' => false, 'sync_interval_minutes' => 1,
        ]);
        Http::fake();
        $actor = Admin::factory()->global()->create();
        $actor->givePermissionTo(Permission::firstOrCreate(['name' => \App\PermKeys::NOTIFICATIONS_CONTROL, 'guard_name' => 'admin']));
        $this->actingAs($actor, 'admin');
    }

    private function payload(): array
    {
        $rules = NotificationAlertSettings::all();
        foreach ($rules as &$rule) { $rule['enabled'] = 0; $rule['template_id'] = null; }
        unset($rule);
        $rules['rent_contract_expiry'] = array_replace($rules['rent_contract_expiry'], [
            'enabled' => 1, 'channels' => ['sms', 'whatsapp'], 'recipient' => 'client',
            'lead_time_value' => 5, 'lead_time_unit' => 'days', 'template_id' => null,
        ]);
        return ['section' => 'alerts', 'alerts' => $rules, 'custom_alerts' => []] + AutomationSettings::all();
    }

    public function test_renewal_link_cannot_be_enabled_without_payment_methods(): void
    {
        $payload = $this->payload();
        $payload['alerts']['rent_contract_expiry']['include_renewal_link'] = 1;
        $payload['alerts']['rent_contract_expiry']['renewal_payment_methods'] = [];
        $this->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasErrors('alerts.rent_contract_expiry.renewal_payment_methods');
    }

    public function test_unknown_renewal_payment_method_is_rejected(): void
    {
        $payload = $this->payload();
        $payload['alerts']['rent_contract_expiry']['renewal_payment_methods'] = ['forged'];
        $this->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasErrors('alerts.rent_contract_expiry.renewal_payment_methods.0');
    }

    public function test_customer_renewal_link_switch_is_saved_and_can_be_disabled(): void
    {
        $payload = $this->payload();
        $payload['alerts']['rent_contract_expiry']['include_renewal_link'] = 1;
        $payload['alerts']['rent_contract_expiry']['renewal_payment_methods'] = ['bank_transfer', 'cash'];
        $this->post(route('notifications.settings.update'), $payload)->assertSessionHasNoErrors();
        $this->assertTrue(NotificationAlertSettings::rule('rent_contract_expiry')['include_renewal_link']);
        $this->assertSame(['bank_transfer', 'cash'], NotificationAlertSettings::rule('rent_contract_expiry')['renewal_payment_methods']);
        $this->get(route('notifications.settings.alerts'))->assertOk()->assertSee('إرفاق رابط التمديد / طلب تسليم العاملة');
        $payload['alerts']['rent_contract_expiry']['include_renewal_link'] = 0;
        $this->post(route('notifications.settings.update'), $payload)->assertSessionHasNoErrors();
        $this->assertFalse(NotificationAlertSettings::rule('rent_contract_expiry')['include_renewal_link']);
        Http::assertNothingSent();
    }

    public function test_rental_reminder_with_default_message_survives_save_and_reload(): void
    {
        $this->post(route('notifications.settings.update'), $this->payload())->assertSessionHasNoErrors()->assertSessionHas('success');
        $rule = NotificationAlertSettings::rule('rent_contract_expiry');
        $this->assertTrue($rule['enabled']);
        $this->assertSame(['sms','whatsapp'], $rule['channels']);
        $this->assertSame('client', $rule['recipient']);
        $this->assertTrue(app(\App\Services\RentalContractExpiryReminderService::class)->isEnabled());
        $this->get(route('notifications.settings.alerts'))->assertOk()
            ->assertViewHas('alertSettings', fn ($rules) => $rules['rent_contract_expiry']['enabled'] && $rules['rent_contract_expiry']['channels'] === ['sms','whatsapp'])
            ->assertSee('النص الافتراضي لتنبيه انتهاء التأجير');
        Http::assertNothingSent();
    }

    public function test_enabled_alert_without_channels_is_rejected_without_silently_changing_to_internal_notification(): void
    {
        $before = NotificationAlertSettings::rule('rent_contract_expiry');
        $payload = $this->payload();
        $payload['alerts']['rent_contract_expiry']['channels'] = [];
        $this->from(route('notifications.settings.alerts'))->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasErrors('alerts.rent_contract_expiry.channels');
        $this->assertSame($before, NotificationAlertSettings::rule('rent_contract_expiry'));
        $this->get(route('notifications.settings.alerts'))->assertOk()->assertSee('alert-save-errors');
        Http::assertNothingSent();
    }

    public function test_other_enabled_alerts_still_require_their_template_and_old_choices_are_retained(): void
    {
        $payload = $this->payload();
        $payload['alerts']['recruitment_orders']['enabled'] = 1;
        $this->from(route('notifications.settings.alerts'))->post(route('notifications.settings.update'), $payload)
            ->assertSessionHasErrors('alerts.recruitment_orders.template_id');
        $response = $this->get(route('notifications.settings.alerts'))->assertOk();
        $response->assertSee('alert-save-errors')->assertSee('scrollIntoView', false);
        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/name="alerts\[rent_contract_expiry\]\[enabled\]" value="1"[^>]*checked/s', $html);
        Http::assertNothingSent();
    }

    public function test_changing_channels_and_disabling_are_persisted(): void
    {
        $payload = $this->payload();
        $this->post(route('notifications.settings.update'), $payload)->assertSessionHasNoErrors();
        $payload['alerts']['rent_contract_expiry']['channels'] = ['whatsapp'];
        $this->post(route('notifications.settings.update'), $payload)->assertSessionHasNoErrors();
        $this->assertSame(['whatsapp'], NotificationAlertSettings::rule('rent_contract_expiry')['channels']);
        $payload['alerts']['rent_contract_expiry']['enabled'] = 0;
        $this->post(route('notifications.settings.update'), $payload)->assertSessionHasNoErrors();
        $this->assertFalse(app(\App\Services\RentalContractExpiryReminderService::class)->isEnabled());
        Http::assertNothingSent();
    }

    public function test_rendered_controls_keep_saved_values_with_the_production_global_settings_composer(): void
    {
        // AppServiceProvider skips this composer in console tests, but registers it for HTTP in production.
        \Illuminate\Support\Facades\View::composer('*', function ($view) {
            $view->with('settings', new \App\Models\ingaz\Setting());
        });
        $this->post(route('notifications.settings.update'), $this->payload())->assertSessionHasNoErrors()->assertSessionHas('success');
        $html = $this->get(route('notifications.settings.alerts'))->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/name="alerts\[rent_contract_expiry\]\[enabled\]" value="1"[^>]*checked/s', $html);
        $this->assertMatchesRegularExpression('/name="alerts\[rent_contract_expiry\]\[channels\]\[\]"\s+value="sms"[^>]*checked/s', $html);
        $this->assertMatchesRegularExpression('/name="alerts\[rent_contract_expiry\]\[channels\]\[\]"\s+value="whatsapp"[^>]*checked/s', $html);
        $this->assertMatchesRegularExpression('/name="alerts\[rent_contract_expiry\]\[recipient\]"[^>]*>\s*<option value="client"[^>]*selected/s', $html);
    }
}
