<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Models\Branch;
use App\PermKeys;
use App\Support\PaymentMessageTemplates;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{Cache, DB};
use Tests\TestCase;

/** صفحة تقارير مدفوعات ميسر وصلاحياتها، وحفظ نصوص الفاتورة والرسائل من إعدادات ميسر. */
class MoyasarPaymentsReportPageTest extends TestCase
{
    use DatabaseTransactions;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::forget(PaymentMessageTemplates::CACHE_KEY);
        $this->branch = Branch::factory()->main()->create();
    }

    private function administrator(): Admin
    {
        $admin = Admin::factory()->superAdmin()->forBranch($this->branch)->create();
        $admin->assignRole('Administrator');

        return $admin;
    }

    private function adminWith(array $permissions): Admin
    {
        $admin = Admin::factory()->forBranch($this->branch)->create();
        $admin->givePermissionTo($permissions);

        return $admin;
    }

    private function paidRentalInvoice(): string
    {
        $userId = DB::table('users')->insertGetId(['name' => 'عميل ميسر', 'phone_code' => '966', 'phone' => '500000099',
            'password' => bcrypt('secret'), 'created_at' => now(), 'updated_at' => now()]);
        $contractId = DB::table('rental_contracts')->insertGetId(['user_id' => $userId, 'status' => 'active',
            'contract_number' => 'RPT-' . uniqid(), 'created_at' => now(), 'updated_at' => now()]);
        $invoice = 'inv-report-' . uniqid();
        DB::table('moyasar_contract_payments')->insert(['rental_contract_id' => $contractId, 'invoice_id' => $invoice,
            'amount' => 1250, 'refunded_amount' => 250, 'paid_at' => now(), 'created_at' => now(), 'updated_at' => now()]);

        return $invoice;
    }

    public function test_report_lists_contract_payments_with_their_refunds(): void
    {
        $invoice = $this->paidRentalInvoice();
        $this->actingAs($this->administrator(), 'admin');

        $this->get(route('moyasar-payments.index'))
            ->assertOk()
            ->assertSee(__('payment_gateways.moyasar.title'))
            ->assertSee('عميل ميسر');

        $this->get(route('moyasar-payments.index', ['service' => 'rental', 'status' => 'partially_refunded', 'search' => $invoice]))
            ->assertOk()
            ->assertSee('1,250.00')
            ->assertSee(__('payment_gateways.status.partially_refunded'));
    }

    public function test_exports_follow_their_own_permission(): void
    {
        $this->paidRentalInvoice();
        $this->actingAs($this->adminWith([PermKeys::PAYMENTS_MOYASAR_VIEW]), 'admin');
        $this->get(route('moyasar-payments.index'))->assertOk()->assertDontSee(__('payment_gateways.export.excel'));
        $this->get(route('moyasar-payments.export', ['format' => 'xlsx']))->assertForbidden();

        $this->actingAs($this->administrator(), 'admin');
        $xlsx = $this->get(route('moyasar-payments.export', ['format' => 'xlsx']));
        $xlsx->assertOk();
        $this->assertStringContainsString('spreadsheetml', (string) $xlsx->headers->get('Content-Type'));
        $pdf = $this->get(route('moyasar-payments.export', ['format' => 'pdf']));
        $pdf->assertOk()->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringStartsWith('%PDF', $pdf->getContent());
    }

    public function test_super_admin_switch_hides_the_report_and_its_finance_menu_link(): void
    {
        $this->actingAs($this->administrator(), 'admin');
        $menu = view('finance::finance')->render();
        $this->assertStringContainsString(route('moyasar-payments.index'), $menu);
        // الرابط يأتي بعد لوحة تحكم المالية مباشرة.
        $this->assertLessThan(strpos($menu, route('moyasar-payments.index')), strpos($menu, route('finance.home.index')));

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->put(route('dev-superadmin.moyasar-settings.update'), ['is_enabled' => 0, 'reports_enabled' => 0, 'base_url' => 'https://api.moyasar.com/v1'])
            ->assertRedirect(route('dev-superadmin.moyasar-settings'));

        $this->assertFalse(\App\Support\MoyasarSettings::reportsEnabled());
        $this->get(route('moyasar-payments.index'))->assertNotFound();
        $this->get(route('moyasar-payments.export', ['format' => 'xlsx']))->assertNotFound();
        $this->assertStringNotContainsString(route('moyasar-payments.index'), view('finance::finance')->render());
    }

    public function test_report_is_hidden_without_permission(): void
    {
        $this->actingAs($this->adminWith([PermKeys::MOYASAR_SETTINGS]), 'admin');

        $this->getJson(route('moyasar-payments.index'))->assertForbidden();
    }

    public function test_settings_live_in_the_super_admin_panel_only(): void
    {
        $this->get(route('dev-superadmin.moyasar-settings'))->assertRedirect(route('superadmin.login'));
        $this->put(route('dev-superadmin.moyasar-settings.update'), ['base_url' => 'https://api.moyasar.com/v1'])
            ->assertRedirect(route('superadmin.login'));

        $this->actingAs($this->administrator(), 'admin');
        $this->get(route('moyasar-settings.edit'))->assertRedirect(route('dev-superadmin.moyasar-settings'));
    }

    public function test_connection_test_reports_a_rejected_secret_key(): void
    {
        DB::table('moyasar_settings')->insert(['is_enabled' => false, 'base_url' => 'https://api.moyasar.com/v1',
            'publishable_key' => 'pk_test_1', 'secret_key' => 'sk_test_bad', 'created_at' => now(), 'updated_at' => now()]);
        \Illuminate\Support\Facades\Http::fake(['*/invoices*' => \Illuminate\Support\Facades\Http::response(['message' => 'Invalid'], 401)]);

        $this->withSession(['dev_superadmin_authenticated' => true])
            ->postJson(route('dev-superadmin.moyasar-settings.test'))
            ->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_settings_save_invoice_text_and_message_templates_without_touching_keys(): void
    {
        $this->withSession(['dev_superadmin_authenticated' => true]);
        $this->get(route('dev-superadmin.moyasar-settings'))->assertOk()
            ->assertSee('payment_messages[rental]', false)
            ->assertSee('إعدادات بوابة ميسر');

        $before = DB::table('moyasar_settings')->latest('id')->first();
        $this->put(route('dev-superadmin.moyasar-settings.update'), [
            'is_enabled' => 0, 'base_url' => 'https://api.moyasar.com/v1',
            'company_name' => 'شركة كليك', 'payment_description' => 'خدمات العمالة',
            'payment_messages' => ['company_name' => '', 'rental' => "مرحباً {customer_name}\n{payment_link}", 'transfer' => ''],
        ])->assertRedirect(route('dev-superadmin.moyasar-settings'));

        $saved = DB::table('moyasar_settings')->latest('id')->first();
        $this->assertSame('شركة كليك', $saved->company_name);
        $this->assertSame((string) ($before->secret_key ?? ''), (string) $saved->secret_key);
        $this->assertSame("مرحباً {customer_name}\n{payment_link}", PaymentMessageTemplates::template('rental'));
        $this->assertSame(PaymentMessageTemplates::defaultTemplate('transfer'), PaymentMessageTemplates::template('transfer'));

        $this->put(route('dev-superadmin.moyasar-settings.update'), [
            'is_enabled' => 0, 'base_url' => 'https://api.moyasar.com/v1',
            'payment_messages' => ['rental' => 'نص بلا رابط'],
        ])->assertSessionHasErrors('payment_messages.rental');
    }
}
