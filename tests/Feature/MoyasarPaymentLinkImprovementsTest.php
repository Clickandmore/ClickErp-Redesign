<?php

namespace Tests\Feature;

use App\Models\{RentalContract, RentalContractInfo, SponsorshipTransfer};
use App\Services\{MoyasarService, PaymentLinkDeliveryService, RentalContractMoyasarLinkService, SponsorshipTransferMoyasarService, UltraMsgWhatsAppService};
use App\Services\ingaz\{RentalContractFinanceService, SponsorshipTransferPaymentService};
use App\Services\PaymentGateways\MoyasarPaymentsReport;
use App\Support\{MoyasarSettings, PaymentMessageTemplates};
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Cache, DB, Http, Schema};
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/** تحسينات ميسر المنقولة من أسس: الإرسال، القوالب، الإلغاء عند الإغلاق، ونقل الخدمات. */
class MoyasarPaymentLinkImprovementsTest extends TestCase
{
    private $dispatcher;
    private $gateway;

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.moyasar_links' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
            'database.default' => 'moyasar_links', 'activitylog.enabled' => false]);
        $this->dispatcher = Model::getEventDispatcher();
        Model::unsetEventDispatcher();
        Cache::flush();

        Schema::create('moyasar_settings', function (Blueprint $t) {
            $t->id(); $t->boolean('is_enabled')->default(false); $t->string('base_url')->default('https://api.moyasar.com/v1');
            $t->string('publishable_key')->nullable(); $t->string('secret_key')->nullable(); $t->string('webhook_secret')->nullable();
            $t->unsignedBigInteger('created_by')->nullable(); $t->unsignedBigInteger('updated_by')->nullable(); $t->timestamps();
        });
        Schema::create('new_settings', function (Blueprint $t) { $t->id(); $t->string('key'); $t->text('value')->nullable(); $t->timestamps(); });
        Schema::create('users', function (Blueprint $t) { $t->id(); $t->string('name')->nullable(); $t->string('phone')->nullable(); $t->softDeletes(); $t->timestamps(); });
        Schema::create('rental_contracts', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('user_id')->nullable(); $t->string('status')->default('active'); $t->string('contract_number')->nullable();
            $t->timestamp('closed_at')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->timestamps();
        });
        Schema::create('rental_contract_infos', function (Blueprint $t) {
            $t->id(); $t->unsignedBigInteger('rental_contract_id'); $t->string('payment_type')->default('moyasar'); $t->string('payment_status')->default('pending');
            $t->decimal('contract_value', 12, 2)->default(1000); $t->decimal('paid_amount', 12, 2)->default(0); $t->decimal('remaining_amount', 12, 2)->default(1000);
            $t->string('moyasar_payment_id')->nullable(); $t->string('moyasar_payment_url', 2048)->nullable(); $t->string('moyasar_payment_status')->nullable();
            $t->timestamp('payment_link_sent_at')->nullable(); $t->timestamp('moyasar_paid_at')->nullable(); $t->timestamps();
        });
        Schema::create('rental_contract_expiry_reminders', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('rental_contract_id'); $t->string('payment_id')->nullable(); $t->timestamps(); });
        Schema::create('rental_order_payments', function (Blueprint $t) { $t->id(); });
        Schema::create('hourly_order_payments', function (Blueprint $t) { $t->id(); });
        Schema::create('sponsorship_transfers', function (Blueprint $t) {
            $t->id(); $t->string('workflow_source')->default('standard'); $t->string('status')->default('waiting_contract_send');
            $t->unsignedBigInteger('new_sponsor_id')->nullable(); $t->decimal('contract_value', 12, 2)->default(3000);
            $t->decimal('paid_amount', 12, 2)->default(0); $t->decimal('remaining_amount', 12, 2)->default(3000);
            $t->string('payment_type')->default('moyasar'); $t->string('payment_status')->default('pending');
            $t->unsignedBigInteger('receipt_account_id')->nullable(); $t->unsignedBigInteger('receipt_bond_id')->nullable();
            $t->timestamp('payment_confirmed_at')->nullable(); $t->unsignedBigInteger('branch_id')->nullable(); $t->softDeletes(); $t->timestamps();
        });
        (require base_path('database/migrations/2026_09_29_180000_add_moyasar_refund_tracking.php'))->up();
        (require base_path('database/migrations/2026_10_26_120000_add_moyasar_payment_link_improvements.php'))->up();

        DB::table('moyasar_settings')->insert(['is_enabled' => true, 'publishable_key' => 'pk_test', 'secret_key' => 'sk_test', 'webhook_secret' => 'whsec']);
        DB::table('users')->insert(['id' => 1, 'name' => 'سارة', 'phone' => '0500000001']);
        DB::table('rental_contracts')->insert(['id' => 1, 'user_id' => 1, 'contract_number' => 'R-100']);
        DB::table('rental_contract_infos')->insert(['id' => 1, 'rental_contract_id' => 1]);

        $this->gateway = \Mockery::mock(MoyasarService::class);
        app()->instance(MoyasarService::class, $this->gateway);
        app()->instance(RentalContractFinanceService::class, \Mockery::mock(RentalContractFinanceService::class));
        app()->instance(\App\Services\RentalContractExpiryReminderService::class, \Mockery::mock(\App\Services\RentalContractExpiryReminderService::class));
    }

    protected function tearDown(): void
    {
        PaymentLinkDeliveryService::forgetAvailableChannels();
        Model::setEventDispatcher($this->dispatcher);
        parent::tearDown();
    }

    public function test_hourly_webhook_accepts_moyasar_secret_token_or_valid_hmac_only(): void
    {
        $gateway = new \App\Services\Hourly\HourlyMoyasarGateway();
        $payload = json_encode(['secret_token' => 'whsec', 'data' => ['id' => 'pay-1']], JSON_THROW_ON_ERROR);
        $unsigned = json_encode(['data' => ['id' => 'pay-1']], JSON_THROW_ON_ERROR);

        $this->assertTrue($gateway->verifyWebhookSignature($payload, ''));
        $this->assertTrue($gateway->verifyWebhookSignature($unsigned, hash_hmac('sha256', $unsigned, 'whsec')));
        $this->assertFalse($gateway->verifyWebhookSignature($unsigned, 'invalid'));
        $this->assertFalse($gateway->verifyWebhookSignature($unsigned, ''));
    }

    /** القنوات تُقرأ من إعدادات الاتصال؛ هنا تُضبط مباشرة. */
    private function channels(bool $sms, bool $whatsapp): void
    {
        $property = new \ReflectionProperty(PaymentLinkDeliveryService::class, 'availableChannels');
        $property->setAccessible(true);
        $property->setValue(null, ['sms' => $sms, 'whatsapp' => $whatsapp, 'copy' => true]);
    }

    private function invoice(string $id, string $status, int $amount, int $refund = 0): array
    {
        $payments = in_array($status, ['paid', 'refunded'], true)
            ? [['id' => 'pay-' . $id, 'status' => $refund ? 'refunded' : 'paid', 'amount' => $amount, 'currency' => 'SAR', 'refunded' => $refund]]
            : [];

        return ['id' => $id, 'status' => $status, 'amount' => $amount, 'currency' => 'SAR', 'payments' => $payments];
    }

    public function test_default_rental_message_is_the_text_sent_before_templates(): void
    {
        $message = PaymentMessageTemplates::render('rental', [
            'customer_name' => 'سارة', 'contract_number' => 'R-100',
            'amount' => PaymentMessageTemplates::amount(1500), 'payment_link' => 'https://pay.test/1',
        ]);

        $this->assertSame(implode("\n", [
            'عميلنا سارة،',
            'تم إنشاء عقد إيجار رقم R-100 بقيمة 1,500.00 ريال.',
            'يمكنك إتمام الدفع إلكترونياً عبر الرابط:',
            'https://pay.test/1',
            'شكراً لتعاملكم معنا.',
        ]), $message);
    }

    public function test_custom_template_drops_incomplete_lines_and_default_text_is_stored_empty(): void
    {
        PaymentMessageTemplates::save(['company_name' => '', 'rental' => "{company_name}\nرابطك: {payment_link}", 'transfer' => PaymentMessageTemplates::defaultTemplate('transfer')]);

        $this->assertSame('رابطك: https://pay.test/1', PaymentMessageTemplates::render('rental', ['payment_link' => 'https://pay.test/1']));
        $this->assertSame('', DB::table('new_settings')->where('key', PaymentMessageTemplates::key('transfer'))->value('value'));

        PaymentMessageTemplates::save(['company_name' => 'كليك', 'rental' => '', 'transfer' => '']);
        $this->assertSame(PaymentMessageTemplates::defaultTemplate('rental'), PaymentMessageTemplates::template('rental'));
        $this->assertSame('كليك', PaymentMessageTemplates::companyName());
    }

    public function test_invoice_text_is_unchanged_until_configured_and_customer_returns_to_success_url(): void
    {
        $this->assertSame('دفع عقد', MoyasarSettings::invoiceDescription('دفع عقد'));
        DB::table('moyasar_settings')->update(['company_name' => 'كليك', 'payment_description' => 'خدمات التأجير']);
        $this->assertSame('كليك — خدمات التأجير — دفع عقد', MoyasarSettings::invoiceDescription('دفع عقد'));

        Http::fake(['*/invoices' => Http::response(['id' => 'inv-1', 'url' => 'https://checkout.moyasar.com/inv-1'])]);
        $result = (new MoyasarService())->createPaymentLink(10, 'دفع عقد', 'https://erp.test/cb', [], 'https://erp.test/cb');
        $this->assertSame('inv-1', $result['id']);
        Http::assertSent(fn ($request) => $request['success_url'] === 'https://erp.test/cb'
            && $request['description'] === 'كليك — خدمات التأجير — دفع عقد' && $request['amount'] === 1000);
    }

    public function test_rental_link_is_reused_and_replaced_only_after_it_closes(): void
    {
        $this->gateway->shouldReceive('createPaymentLink')->once()
            ->withArgs(fn ($amount, $description, $callback, $metadata, $success) => $amount === 1000.0 && $success === $callback)
            ->andReturn(['id' => 'inv-1', 'url' => 'https://checkout.moyasar.com/inv-1']);
        $links = app(RentalContractMoyasarLinkService::class);

        $first = $links->ensureLink(RentalContract::find(1));
        $again = $links->ensureLink(RentalContract::find(1));
        $this->assertTrue($first['created']);
        $this->assertFalse($again['created']);
        $this->assertSame('inv-1', $again['id']);
        $this->assertSame(1, DB::table('moyasar_contract_payments')->where('invoice_id', 'inv-1')->count());

        RentalContractInfo::find(1)->update(['moyasar_payment_status' => 'expired']);
        $this->gateway->shouldReceive('createPaymentLink')->once()->andReturn(['id' => 'inv-2', 'url' => 'https://checkout.moyasar.com/inv-2']);
        $this->assertSame('inv-2', $links->ensureLink(RentalContract::find(1))['id']);
        $this->assertSame('initiated', RentalContractInfo::find(1)->moyasar_payment_status);
    }

    public function test_closing_cancels_open_invoices_and_stops_when_the_customer_just_paid(): void
    {
        RentalContractInfo::find(1)->update(['moyasar_payment_id' => 'inv-1', 'moyasar_payment_status' => 'initiated']);
        DB::table('moyasar_contract_payments')->insert([
            ['rental_contract_id' => 1, 'invoice_id' => 'inv-1', 'amount' => 1000],
            ['rental_contract_id' => 1, 'invoice_id' => 'inv-old', 'amount' => 1000],
        ]);
        DB::table('moyasar_contract_payments')->insert(['rental_contract_id' => 1, 'invoice_id' => 'inv-other-account', 'amount' => 1000]);
        $this->gateway->shouldReceive('lookupInvoice')->with('inv-1')->andReturn(['found' => true, 'invoice' => $this->invoice('inv-1', 'initiated', 100000)]);
        $this->gateway->shouldReceive('lookupInvoice')->with('inv-old')->andReturn(['found' => true, 'invoice' => $this->invoice('inv-old', 'expired', 100000)]);
        // فاتورة أُنشئت بمفاتيح أخرى لا توجد لدى الحساب الحالي، فلا تمنع الإغلاق.
        $this->gateway->shouldReceive('lookupInvoice')->with('inv-other-account')->andReturn(['found' => false, 'invoice' => null]);
        $this->gateway->shouldReceive('cancelInvoice')->once()->with('inv-1')->andReturn(['id' => 'inv-1', 'status' => 'canceled']);

        app(RentalContractMoyasarLinkService::class)->cancelOpenInvoices(RentalContract::find(1));
        $this->assertSame('canceled', RentalContractInfo::find(1)->moyasar_payment_status);

        $unreachable = \Mockery::mock(MoyasarService::class);
        $unreachable->shouldReceive('lookupInvoice')->andReturn(['found' => null, 'invoice' => null]);
        $unreachable->shouldNotReceive('cancelInvoice');
        app()->instance(MoyasarService::class, $unreachable);
        RentalContractInfo::find(1)->update(['moyasar_payment_status' => 'initiated']);
        try {
            app(RentalContractMoyasarLinkService::class)->cancelOpenInvoices(RentalContract::find(1));
            $this->fail('An unreachable gateway must stop the closure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment', $exception->errors());
        }

        $gateway = \Mockery::mock(MoyasarService::class);
        app()->instance(MoyasarService::class, $gateway);
        $gateway->shouldReceive('lookupInvoice')->with('inv-old')->andReturn(['found' => true, 'invoice' => $this->invoice('inv-old', 'expired', 100000)]);
        $gateway->shouldReceive('lookupInvoice')->with('inv-other-account')->andReturn(['found' => false, 'invoice' => null]);
        $gateway->shouldReceive('lookupInvoice')->with('inv-1')->andReturn(['found' => true, 'invoice' => $this->invoice('inv-1', 'paid', 100000)]);
        $gateway->shouldNotReceive('cancelInvoice');
        $payments = \Mockery::mock(\App\Services\MoyasarContractPaymentService::class);
        $payments->shouldReceive('resolve')->with('inv-1')->andReturn(new \App\Models\MoyasarContractPayment());
        $payments->shouldReceive('synchronize')->once();
        app()->instance(\App\Services\MoyasarContractPaymentService::class, $payments);

        $this->expectException(ValidationException::class);
        app(RentalContractMoyasarLinkService::class)->cancelOpenInvoices(RentalContract::find(1));
    }

    public function test_link_is_sent_by_whatsapp_to_the_edited_phone_and_copy_sends_nothing(): void
    {
        RentalContractInfo::find(1)->update(['moyasar_payment_id' => 'inv-1', 'moyasar_payment_url' => 'https://checkout.moyasar.com/inv-1', 'moyasar_payment_status' => 'initiated']);
        DB::table('moyasar_contract_payments')->insert(['rental_contract_id' => 1, 'invoice_id' => 'inv-1', 'amount' => 1000]);
        $this->gateway->shouldReceive('getInvoice')->with('inv-1')->andReturn($this->invoice('inv-1', 'initiated', 100000));
        $this->gateway->shouldNotReceive('createPaymentLink');
        $whatsapp = \Mockery::mock(UltraMsgWhatsAppService::class);
        $whatsapp->shouldReceive('sendChatMessage')->once()
            ->withArgs(fn ($phone, $message) => $phone === '0555555555' && str_contains($message, 'https://checkout.moyasar.com/inv-1') && str_contains($message, 'R-100'))
            ->andReturn(['success' => true]);
        app()->instance(UltraMsgWhatsAppService::class, $whatsapp);
        $this->channels(false, true);
        $delivery = app(PaymentLinkDeliveryService::class);

        // SMS معطل: يبقى الرابط جاهزاً ولا يُرسل شيء.
        $blocked = $delivery->deliverRental(RentalContract::find(1), 'sms', '0555555555');
        $this->assertTrue($blocked['link_created']);
        $this->assertFalse($blocked['link_sent']);
        $this->assertStringContainsString('الرسائل النصية غير مفعل', $blocked['message']);

        $copy = $delivery->deliverRental(RentalContract::find(1), 'copy');
        $this->assertSame('https://checkout.moyasar.com/inv-1', $copy['payment_url']);
        $this->assertNull(RentalContractInfo::find(1)->payment_link_sent_at);

        $sent = $delivery->deliverRental(RentalContract::find(1), 'whatsapp', '0555555555');
        $this->assertTrue($sent['link_sent']);
        $this->assertSame('whatsapp', RentalContractInfo::find(1)->payment_link_sent_channel);
        $this->assertSame('إعادة إرسال رابط الدفع', PaymentLinkDeliveryService::label(RentalContract::with('info')->find(1)));
    }

    public function test_transfer_payment_fields_are_gateway_owned_once_an_invoice_exists(): void
    {
        SponsorshipTransferMoyasarService::validateManualPayment(['payment_type' => 'moyasar', 'payment_status' => 'pending', 'paid_amount' => 0, 'contract_value' => 3000]);

        try {
            SponsorshipTransferMoyasarService::validateManualPayment(['payment_type' => 'moyasar', 'payment_status' => 'paid', 'paid_amount' => 3000, 'contract_value' => 3000]);
            $this->fail('Moyasar must not accept a manually recorded payment.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('payment_status', $exception->errors());
        }

        DB::table('sponsorship_transfers')->insert(['id' => 7, 'moyasar_invoice_id' => 'inv-t', 'moyasar_amount' => 3000]);
        $this->expectException(ValidationException::class);
        SponsorshipTransferMoyasarService::validateManualPayment(
            ['payment_type' => 'cash', 'payment_status' => 'pending', 'paid_amount' => 0, 'contract_value' => 3000],
            SponsorshipTransfer::find(7)
        );
    }

    public function test_transfer_payment_and_refunds_post_bonds_once(): void
    {
        DB::table('sponsorship_transfers')->insert(['id' => 7, 'new_sponsor_id' => 1, 'receipt_account_id' => 11]);
        $this->gateway->shouldReceive('createPaymentLink')->once()
            ->withArgs(fn ($amount, $description, $callback, $metadata, $success) => $amount === 3000.0 && str_contains($callback, '/transfer/moyasar/callback/') && $success === $callback)
            ->andReturn(['id' => 'inv-t', 'url' => 'https://checkout.moyasar.com/inv-t']);
        $finance = \Mockery::mock(SponsorshipTransferPaymentService::class);
        $finance->shouldReceive('recordGatewayReceipt')->once()->withArgs(fn ($transfer, $amount, $ref) => $amount === 3000.0 && $ref === 'inv-t')
            ->andReturn(['bond' => (object) ['id' => 55]]);
        $finance->shouldReceive('recordGatewayRefund')->once()->withArgs(fn ($transfer, $amount, $ref, $receipt) => $amount === 500.0 && $receipt === 55)
            ->andReturn((new \Nami\Finance\Models\Bond())->forceFill(['id' => 56]));
        app()->instance(SponsorshipTransferPaymentService::class, $finance);
        $payments = app(SponsorshipTransferMoyasarService::class);

        $transfer = $payments->ensureLink(SponsorshipTransfer::find(7));
        $this->assertSame('inv-t', $transfer->moyasar_invoice_id);
        $this->assertNotEmpty($transfer->moyasar_callback_token);

        $this->gateway->shouldReceive('getInvoice')->with('inv-t')->andReturn(
            $this->invoice('inv-t', 'paid', 300000), $this->invoice('inv-t', 'paid', 300000),
            $this->invoice('inv-t', 'refunded', 300000, 50000), $this->invoice('inv-t', 'refunded', 300000, 50000)
        );
        $payments->synchronize($transfer);
        $payments->synchronize($transfer);
        $paid = SponsorshipTransfer::find(7);
        $this->assertSame('3000.00', $paid->paid_amount);
        $this->assertSame('paid', $paid->payment_status);
        $this->assertSame(55, (int) $paid->moyasar_receipt_bond_id);

        $payments->synchronize($paid);
        $payments->synchronize($paid);
        $refunded = SponsorshipTransfer::find(7);
        $this->assertSame('2500.00', $refunded->paid_amount);
        $this->assertSame('500.00', $refunded->remaining_amount);
        $this->assertSame('partially_refunded', $refunded->moyasar_payment_status);
        $this->assertSame(1, DB::table('sponsorship_transfer_moyasar_refunds')->count());
        $this->assertFalse(SponsorshipTransferMoyasarService::canSend($refunded));
    }

    public function test_transfer_callback_confirms_from_moyasar_and_rejects_unknown_tokens(): void
    {
        DB::table('sponsorship_transfers')->insert(['id' => 7, 'moyasar_invoice_id' => 'inv-t', 'moyasar_amount' => 3000,
            'moyasar_callback_token' => 'b3c1f5c2-1111-4222-8333-444455556666', 'moyasar_payment_status' => 'initiated']);
        $this->gateway->shouldReceive('getInvoice')->with('inv-t')->andReturn($this->invoice('inv-t', 'initiated', 300000));

        $this->post('/transfer/moyasar/callback/b3c1f5c2-1111-4222-8333-444455556666')->assertOk()->assertJson(['received' => true]);
        $this->get('/transfer/moyasar/callback/b3c1f5c2-1111-4222-8333-444455556666')->assertOk()->assertSee('لم يتأكد السداد بعد.');
        $this->post('/transfer/moyasar/callback/00000000-0000-4000-8000-000000000000')->assertNotFound();
    }

    public function test_report_statuses_follow_refunds_and_closed_invoices(): void
    {
        $this->assertSame('cancelled', MoyasarPaymentsReport::normalise('expired'));
        $this->assertSame('pending', MoyasarPaymentsReport::normalise('initiated'));
        $this->assertSame('paid', MoyasarPaymentsReport::normalise('captured'));
    }
}
