<?php

namespace Tests\Feature;

use App\Models\RentalContract;
use App\Models\RentalContractExpiryReminder;
use App\Models\RentalContractExtension;
use App\Models\MoyasarContractPayment;
use App\Models\ingaz\User;
use App\Services\RentalContractCustomerActionService;
use App\Services\RentalContractExpiryReminderService;
use App\Services\MoyasarService;
use App\Services\WhatsApp\WhatsAppMessagingService;
use App\Services\ingaz\RentalContractFinanceService;
use App\Support\MoyasarSettings;
use App\Support\NotificationAlertSettings;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class RentalContractCustomerActionTest extends TestCase
{
    use DatabaseTransactions;

    private RentalContractCustomerActionService $actions;
    private $reminders;
    private $finance;

    protected function setUp(): void
    {
        parent::setUp();
        $this->assertStringEndsWith('_testing', DB::connection()->getDatabaseName());
        $this->travelTo(now()->setDate(2026, 11, 8)->setTime(12, 0));
        config(['cache.default' => 'array']);
        Http::fake();
        NotificationAlertSettings::persist(['rent_contract_expiry' => ['renewal_payment_methods' => ['moyasar']]]);
        DB::table('packages')->update(['is_show' => false]);
        $this->finance = Mockery::mock(RentalContractFinanceService::class);
        $this->finance->shouldReceive('currentPricingBasis')->andReturn(['days' => 30, 'gross_total' => 1000, 'daily_rate' => 1000 / 30, 'source' => 'contract'])->byDefault();
        $this->finance->shouldReceive('extensionAmounts')->andReturn(['total_due' => 1000, 'gross_total_before_discount' => 1000]);
        $this->reminders = Mockery::mock(RentalContractExpiryReminderService::class, [Mockery::mock(WhatsAppMessagingService::class)])->makePartial();
        $this->reminders->shouldReceive('notifyResponsibleEmployee')->andReturnNull()->byDefault();
        $this->app->instance(RentalContractFinanceService::class, $this->finance);
        $this->app->instance(RentalContractExpiryReminderService::class, $this->reminders);
        $this->actions = new RentalContractCustomerActionService($this->finance, $this->reminders);
        $this->app->instance(RentalContractCustomerActionService::class, $this->actions);
    }

    protected function tearDown(): void
    {
        $this->travelBack();
        parent::tearDown();
    }

    private function fixture(): array
    {
        $client = User::withoutEvents(fn () => User::create(['name' => 'Renewal customer', 'phone' => Str::random(20)]));
        $id = DB::table('rental_contracts')->insertGetId(['user_id' => $client->id, 'status' => 'active']);
        DB::table('rental_contract_infos')->insert(['rental_contract_id' => $id, 'ingaz_to' => today()->addDays(3),
            'contract_duration_days' => 30, 'contract_value' => 1000, 'moyasar_payment_id' => 'original-invoice',
            'moyasar_payment_url' => 'https://example.test/original', 'moyasar_payment_status' => 'initiated']);
        $contract = RentalContract::with('info')->findOrFail($id);
        $reminder = RentalContractExpiryReminder::create(['rental_contract_id' => $id, 'token' => Str::random(64),
            'expires_on' => $contract->info->ingaz_to, 'notification_type' => 'advance', 'channel' => 'sms', 'status' => 'pending']);
        return [$contract, $reminder];
    }

    private function selectExtension($contract, $reminder)
    {
        $option = $this->actions->options($contract, $reminder)[0];
        return $this->actions->choose($reminder, ['response' => 'extend', 'quote_token' => $option['token'], 'payment_method' => 'moyasar']);
    }

    public function test_offline_payment_is_an_intent_and_does_not_extend_or_create_gateway_payment(): void
    {
        NotificationAlertSettings::persist(['rent_contract_expiry' => ['renewal_payment_methods' => ['bank_transfer', 'cash']]]);
        [$contract, $reminder] = $this->fixture();
        $this->get(route('rental-contract-reminders.show', $reminder->token))->assertOk()
            ->assertSee('value="bank_transfer"', false)->assertSee('value="cash"', false)->assertDontSee('value="moyasar"', false);
        $quote = $this->actions->options($contract, $reminder)[0];
        $gateway = Mockery::mock(MoyasarService::class);
        $gateway->shouldNotReceive('createPaymentLink');
        $this->app->instance(MoyasarService::class, $gateway);
        $this->post(route('rental-contract-reminders.respond', $reminder->token), [
            'response' => 'extend', 'quote_token' => $quote['token'], 'payment_method' => 'bank_transfer',
        ])->assertSessionHasNoErrors()->assertRedirect();
        $selected = $reminder->fresh();
        $this->assertSame('bank_transfer', $selected->customer_payment_method);
        $this->assertNull($selected->extension_id);
        $this->assertNull($selected->payment_id);
        $this->assertSame($contract->info->ingaz_to->toDateString(), $contract->fresh()->info->ingaz_to->toDateString());
        $this->assertNull($this->actions->paymentUrl($selected, $gateway));
        $this->get(route('rental-contract-reminders.show', $reminder->token))->assertOk()->assertDontSee('المتابعة إلى صفحة الدفع');
    }

    public function test_customer_cannot_submit_a_disabled_payment_method(): void
    {
        [$contract, $reminder] = $this->fixture();
        $quote = $this->actions->options($contract, $reminder)[0];
        $this->post(route('rental-contract-reminders.respond', $reminder->token), [
            'response' => 'extend', 'quote_token' => $quote['token'], 'payment_method' => 'cash',
        ])->assertSessionHasErrors('payment_method');
        $this->assertNull($reminder->fresh()->response);
    }

    public function test_customer_page_displays_duration_price_and_new_end_before_payment(): void
    {
        [$contract, $reminder] = $this->fixture();
        $this->get(route('rental-contract-reminders.show', $reminder->token))->assertOk()
            ->assertSee('1,000.00')->assertSee('30 يوم')->assertSee($contract->info->ingaz_to->copy()->addDays(30)->toDateString());
        $this->assertNull($reminder->fresh()->response);
    }

    public function test_customer_is_offered_only_their_subscribed_package(): void
    {
        $package = fn () => DB::table('packages')->insertGetId(['original_price' => 1000, 'price_after_discount' => 1000,
            'price_with_tax' => 1150, 'days_count' => 30, 'rental_type' => 'monthly', 'duration_unit' => 'month', 'is_show' => true]);
        $subscribed = $package();
        $other = $package();
        [$contract, $reminder] = $this->fixture();
        DB::table('rental_contract_infos')->where('rental_contract_id', $contract->id)->update(['package_id' => $subscribed]);
        $options = $this->actions->options($contract->fresh('info'), $reminder);
        $this->assertSame([$subscribed], array_column($options, 'package_id'));
        $this->assertNotContains($other, array_column($options, 'package_id'));
    }

    public function test_return_is_only_an_intent_and_notifies_employee_once(): void
    {
        [$contract, $reminder] = $this->fixture();
        $this->reminders->shouldReceive('notifyResponsibleEmployee')->once()->with(Mockery::type(RentalContractExpiryReminder::class), 'return');
        $date = today()->addDays(4)->toDateString();
        $this->post(route('rental-contract-reminders.respond', $reminder->token), ['response' => 'return',
            'preferred_return_date' => $date, 'response_notes' => 'يرجى التواصل قبل الاستلام'])->assertRedirect();
        $this->post(route('rental-contract-reminders.respond', $reminder->token), ['response' => 'return'])->assertRedirect();
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertNull($contract->fresh()->closed_at);
        $this->assertNull($reminder->fresh()->contract_effect_applied_at);
        $this->assertSame($date, $reminder->fresh()->preferred_return_date->toDateString());
        $this->assertSame('يرجى التواصل قبل الاستلام', $reminder->fresh()->response_notes);
        Http::assertNothingSent();
    }

    public function test_different_reminder_tokens_share_one_decision_per_expiry_cycle(): void
    {
        [$contract, $reminder] = $this->fixture();
        $other = RentalContractExpiryReminder::create(['rental_contract_id' => $contract->id, 'token' => Str::random(64),
            'expires_on' => $reminder->expires_on, 'notification_type' => 'expired', 'channel' => 'sms']);
        $this->actions->choose($reminder, ['response' => 'return']);
        $this->assertSame($reminder->id, $this->actions->resolve($other->token)->id);
        $this->expectException(ValidationException::class);
        $this->actions->choose($other, ['response' => 'extend', 'quote_token' => 'forged']);
    }

    public function test_a_stale_link_cannot_submit_a_decision(): void
    {
        [$contract, $reminder] = $this->fixture();
        $contract->info->update(['ingaz_to' => today()->addDays(40)]);
        $this->postJson(route('rental-contract-reminders.respond', $reminder->token), ['response' => 'return'])->assertUnprocessable();
        $this->assertNull($reminder->fresh()->response);
    }

    public function test_forged_quote_cannot_create_a_renewal_or_change_dates(): void
    {
        [$contract, $reminder] = $this->fixture();
        $this->postJson(route('rental-contract-reminders.respond', $reminder->token), ['response' => 'extend',
            'quote_token' => 'forged', 'amount' => 1, 'days' => 365])->assertUnprocessable();
        $this->assertNull($reminder->fresh()->response);
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
    }

    public function test_unpriced_renewal_records_intent_for_employee_without_charging(): void
    {
        [$contract, $reminder] = $this->fixture();
        $this->finance->shouldReceive('currentPricingBasis')->andReturn(['days' => 0, 'gross_total' => 0]);
        $selected = $this->actions->choose($reminder, ['response' => 'extend']);
        $this->assertSame('extend', $selected->response);
        $this->assertNull($selected->renewal_quote);
        $gateway = Mockery::mock(MoyasarService::class);
        $gateway->shouldNotReceive('createPaymentLink');
        $this->assertNull($this->actions->paymentUrl($selected, $gateway));
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
    }

    public function test_payment_uses_a_dedicated_invoice_and_reuses_it_without_changing_original_balance(): void
    {
        [$contract, $reminder] = $this->fixture();
        MoyasarSettings::persist(['is_enabled' => true, 'publishable_key' => 'test-key', 'secret_key' => 'test-secret']);
        $selected = $this->selectExtension($contract, $reminder);
        $gateway = Mockery::mock(MoyasarService::class);
        $invoice = 'renewal-' . Str::uuid();
        $gateway->shouldReceive('createPaymentLink')->once()->with(1000.0, Mockery::type('string'), Mockery::type('string'),
            Mockery::on(fn ($meta) => $meta['reminder_id'] === $reminder->id), Mockery::type('string'))
            ->andReturn(['id' => $invoice, 'url' => 'https://example.test/renewal']);
        $this->assertSame('https://example.test/renewal', $this->actions->paymentUrl($selected, $gateway));
        $this->assertSame('https://example.test/renewal', $this->actions->paymentUrl($selected, $gateway));
        $this->assertSame('original-invoice', $contract->info->fresh()->moyasar_payment_id);
        $this->assertSame('https://example.test/original', $contract->info->fresh()->moyasar_payment_url);
        $this->assertSame(1, MoyasarContractPayment::where('reminder_id', $reminder->id)->count());
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
    }

    public function test_verified_payment_applies_frozen_quote_once_even_if_rates_change(): void
    {
        [$contract, $reminder] = $this->fixture();
        $selected = $this->selectExtension($contract, $reminder);
        $contract->info->update(['contract_value' => 5000, 'contract_duration_days' => 90]);
        $this->reminders->applyPaidExtensionForMoyasarPayment($contract->info->fresh(), 'verified-payment', $selected->id);
        $this->reminders->applyPaidExtensionForMoyasarPayment($contract->info->fresh(), 'verified-payment', $selected->id);
        $extensions = RentalContractExtension::where('rental_contract_id', $contract->id)->get();
        $this->assertCount(1, $extensions);
        $this->assertSame(30, $extensions->first()->extension_days);
        $this->assertSame('1000.00', $extensions->first()->extension_value);
        $this->assertSame($selected->renewal_quote['end_date'], $contract->info->fresh()->ingaz_to->toDateString());
    }

    public function test_late_payment_does_not_reactivate_a_closed_contract(): void
    {
        [$contract, $reminder] = $this->fixture();
        $selected = $this->selectExtension($contract, $reminder);
        DB::table('rental_contracts')->where('id', $contract->id)->update(['status' => 'completed', 'closed_at' => now()]);
        $this->reminders->applyPaidExtensionForMoyasarPayment($contract->info, 'verified-payment', $selected->id);
        $this->assertSame('completed', $contract->fresh()->status);
        $this->assertSame('extend_review', $selected->fresh()->contract_effect_type);
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
    }

    public function test_payment_after_employee_extension_is_saved_for_review_without_extending_again(): void
    {
        [$contract, $reminder] = $this->fixture();
        $selected = $this->selectExtension($contract, $reminder);
        $newEnd = $contract->info->ingaz_to->copy()->addDays(60)->toDateString();
        $contract->info->update(['ingaz_to' => $newEnd]);
        $this->reminders->applyPaidExtensionForMoyasarPayment($contract->info, 'verified-payment', $selected->id);
        $this->assertSame($newEnd, $contract->info->fresh()->ingaz_to->toDateString());
        $this->assertSame('extend_review', $selected->fresh()->contract_effect_type);
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
    }

    public function test_provider_confirmation_posts_the_extension_and_receipt_once(): void
    {
        [$contract, $reminder] = $this->fixture();
        $selected = $this->selectExtension($contract, $reminder);
        $invoice = 'renewal-' . Str::uuid();
        $pending = ['id' => $invoice, 'currency' => 'SAR', 'amount' => 100000, 'status' => 'initiated', 'payments' => []];
        $paid = array_replace($pending, ['status' => 'paid', 'payments' => [['id' => 'verified-transaction',
            'currency' => 'SAR', 'amount' => 100000, 'status' => 'paid', 'refunded' => 0]]]);
        $gateway = Mockery::mock(MoyasarService::class);
        $gateway->shouldReceive('getInvoice')->times(3)->with($invoice)->andReturn($pending, $paid, $paid);
        $receipt = new \Nami\Finance\Models\Bond();
        $receipt->setRawAttributes(['id' => 9999999, 'debit_account_id' => 9999999, 'constraint_id' => 9999999]);
        $this->finance->shouldReceive('recordExtensionForContract')->once()->andReturnNull();
        $this->finance->shouldReceive('recordMoyasarReceipt')->once()->andReturn($receipt);
        $service = new \App\Services\MoyasarContractPaymentService($gateway, $this->finance, $this->reminders);
        $payment = $service->register($contract->info, $invoice, 1000, $selected->id);
        $service->synchronize($payment);
        $this->assertNull($payment->fresh()->paid_at);
        $this->assertSame(0, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
        $service->synchronize($payment);
        $service->synchronize($payment->fresh());
        $this->assertNotNull($payment->fresh()->paid_at);
        $this->assertSame(1, RentalContractExtension::where('rental_contract_id', $contract->id)->count());
        $extension = RentalContractExtension::findOrFail($selected->fresh()->extension_id);
        $this->assertSame(9999999, (int) $extension->receipt_bond_id);
        $this->assertSame('original-invoice', $contract->info->fresh()->moyasar_payment_id);
    }

    public function test_the_reminder_link_is_only_included_when_its_setting_is_enabled(): void
    {
        [$contract, $reminder] = $this->fixture();
        $contract->load('user');
        $method = new \ReflectionMethod(RentalContractExpiryReminderService::class, 'buildCustomerMessage');
        $method->setAccessible(true);
        NotificationAlertSettings::persist(['rent_contract_expiry' => ['include_renewal_link' => false]]);
        $this->assertStringNotContainsString($reminder->token, $method->invoke($this->reminders, $contract, $reminder));
        NotificationAlertSettings::persist(['rent_contract_expiry' => ['include_renewal_link' => true, 'renewal_payment_methods' => ['moyasar']]]);
        $this->assertStringContainsString(route('rental-contract-reminders.show', $reminder->token), $method->invoke($this->reminders, $contract, $reminder));
    }
}
