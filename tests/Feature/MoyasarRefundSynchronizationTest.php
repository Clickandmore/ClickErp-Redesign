<?php
namespace Tests\Feature;

use App\Models\{MoyasarContractPayment,RentalContractInfo,RentalContractExtension,RentalContractExpiryReminder};
use App\Services\{MoyasarService,MoyasarContractPaymentService,RentalContractExpiryReminderService};
use App\Services\ingaz\RentalContractFinanceService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB,Schema};
use Nami\Finance\Models\Bond;
use Tests\TestCase;

class MoyasarRefundSynchronizationTest extends TestCase
{
    private $dispatcher;
    private $gateway;
    private $finance;
    private $reminders;
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.refund_test'=>['driver'=>'sqlite','database'=>':memory:','prefix'=>''], 'database.default'=>'refund_test','activitylog.enabled'=>false]);
        $this->dispatcher=Model::getEventDispatcher(); Model::unsetEventDispatcher();
        Schema::create('rental_contracts',function(Blueprint $t){$t->id();$t->string('status')->default('active');$t->unsignedBigInteger('marketer_id')->default(7);$t->unsignedBigInteger('branch_id')->default(3);$t->timestamps();});
        Schema::create('rental_contract_infos',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');$t->string('moyasar_payment_id');$t->string('moyasar_payment_status')->default('paid');$t->timestamp('moyasar_paid_at')->nullable();$t->timestamp('payment_confirmed_at')->nullable();$t->string('payment_type')->default('moyasar');$t->string('payment_status')->default('paid');$t->decimal('contract_value',12,2)->default(1000);$t->decimal('paid_amount',12,2)->default(1000);$t->decimal('remaining_amount',12,2)->default(0);$t->date('ingaz_to')->nullable();$t->timestamps();});
        Schema::create('rental_contract_invoices',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');$t->decimal('paid_amount',12,2)->default(1000);$t->decimal('remaining_amount',12,2)->default(0);$t->decimal('total_due',12,2)->default(1000);$t->string('payment_status')->default('paid');$t->boolean('is_paid')->default(true);$t->timestamps();});
        Schema::create('rental_contract_expiry_reminders',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');$t->string('payment_id')->nullable();$t->unsignedBigInteger('extension_id')->nullable();$t->string('payment_status')->default('paid');$t->timestamp('contract_effect_applied_at')->nullable();$t->timestamps();});
        Schema::create('rental_contract_extensions',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id');$t->decimal('extension_value',12,2)->default(1000);$t->decimal('payment_amount',12,2)->default(1000);$t->string('payment_type')->default('moyasar');$t->string('payment_status')->default('paid');$t->unsignedBigInteger('receipt_bond_id')->nullable();$t->timestamps();});
        Schema::create('fn_bonds',function(Blueprint $t){$t->id();$t->unsignedBigInteger('rental_contract_id')->default(1);$t->unsignedBigInteger('debit_account_id')->default(11);$t->unsignedBigInteger('credit_account_id')->default(22);$t->unsignedBigInteger('created_by_id')->default(7);$t->boolean('is_confirm')->default(true);$t->unsignedBigInteger('constraint_id')->default(1);$t->timestamps();});
        Schema::create('rental_order_payments',function(Blueprint $t){$t->id();$t->unsignedBigInteger('order_id')->default(1);$t->string('provider')->default('moyasar');$t->string('provider_invoice_id')->default('invoice-app');$t->string('provider_payment_id')->nullable();$t->decimal('amount',12,2)->default(1000);$t->string('currency')->default('SAR');$t->string('status')->default('pending');$t->timestamp('paid_at')->nullable();$t->json('provider_payload')->nullable();$t->timestamps();});
        Schema::create('orders',function(Blueprint $t){$t->id();$t->string('payment_method')->nullable();$t->softDeletes();$t->timestamps();});
        Schema::create('hourly_order_payments',function(Blueprint $t){$t->id();$t->string('provider')->nullable();$t->string('provider_reference')->nullable();});
        (require base_path('database/migrations/2026_09_29_180000_add_moyasar_refund_tracking.php'))->up();
        DB::table('rental_contracts')->insert(['id'=>1]);
        DB::table('rental_contract_infos')->insert(['id'=>1,'rental_contract_id'=>1,'moyasar_payment_id'=>'invoice-1','moyasar_paid_at'=>now(),'ingaz_to'=>'2030-01-01']);
        DB::table('rental_contract_invoices')->insert(['id'=>1,'rental_contract_id'=>1]);
        DB::table('fn_bonds')->insert(['id'=>1]);
        $this->gateway=\Mockery::mock(MoyasarService::class);
        $this->finance=\Mockery::mock(RentalContractFinanceService::class);
        $this->finance->shouldReceive('syncPaymentForContract')->andReturn(['receipt_bond'=>Bond::find(1)]);
        $this->reminders=\Mockery::mock(RentalContractExpiryReminderService::class);
    }
    protected function tearDown(): void { Model::setEventDispatcher($this->dispatcher); parent::tearDown(); }
    private function service(): MoyasarContractPaymentService { return new MoyasarContractPaymentService($this->gateway,$this->finance,$this->reminders); }
    private function invoice(int $refund): array { return ['id'=>'invoice-1','status'=>'paid','amount'=>100000,'currency'=>'SAR','payments'=>[['id'=>'payment-1','status'=>$refund ? 'refunded':'paid','amount'=>100000,'currency'=>'SAR','refunded'=>$refund]]]; }

    public function test_partial_duplicate_stale_and_full_refunds_preserve_contract_operation(): void
    {
        $this->gateway->shouldReceive('getInvoice')->andReturn($this->invoice(20000),$this->invoice(20000),$this->invoice(0),$this->invoice(100000));
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->withArgs(fn($c,$amount,$ref,$receipt)=>$amount===200.0 && $ref==='invoice-1' && $receipt===1)->andReturn(Bond::find(1));
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->withArgs(fn($c,$amount)=>$amount===800.0)->andReturn(Bond::find(1));
        $service=$this->service();$payment=$service->resolve('invoice-1');
        $service->synchronize($payment);
        $this->assertSame('800.00',RentalContractInfo::find(1)->paid_amount);
        for($i=0;$i<3;$i++)$service->synchronize($payment);
        $info=RentalContractInfo::find(1);
        $this->assertSame('0.00',$info->paid_amount);$this->assertSame('1000.00',$info->moyasar_refunded_amount);
        $this->assertSame('refunded',$info->moyasar_payment_status);
        $this->assertEquals(2,DB::table('moyasar_contract_refunds')->count());
        $this->assertEquals(1000,DB::table('moyasar_contract_refunds')->sum('amount'));
        $this->assertSame('active',DB::table('rental_contracts')->value('status'));
        $this->assertSame('2030-01-01',$info->ingaz_to->toDateString());
    }
    public function test_finance_failure_rolls_back_refund_and_retry_posts_once(): void
    {
        $this->gateway->shouldReceive('getInvoice')->andReturn($this->invoice(20000));
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->andThrow(new \RuntimeException('Financial year unavailable'));
        $service=$this->service();$payment=$service->resolve('invoice-1');
        try {$service->synchronize($payment);$this->fail('Expected failure');}catch(\RuntimeException $e){$this->assertSame('Financial year unavailable',$e->getMessage());}
        $this->assertSame('1000.00',RentalContractInfo::find(1)->paid_amount);
        $this->assertEquals(0,DB::table('moyasar_contract_refunds')->count());
        $this->assertNull($payment->fresh()->paid_at);
    }
    public function test_extension_refund_does_not_reduce_base_payment_or_change_dates(): void
    {
        DB::table('rental_contract_extensions')->insert(['id'=>1,'rental_contract_id'=>1,'receipt_bond_id'=>1]);
        DB::table('rental_contract_expiry_reminders')->insert(['id'=>1,'rental_contract_id'=>1,'payment_id'=>'payment-1','extension_id'=>1,'contract_effect_applied_at'=>now()]);
        $this->gateway->shouldReceive('getInvoice')->andReturn($this->invoice(20000));
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->andReturn(Bond::find(1));
        $this->reminders->shouldNotReceive('applyPaidExtensionForMoyasarPayment');
        $service=$this->service();$service->synchronize($service->resolve('invoice-1'));
        $this->assertSame('1000.00',RentalContractInfo::find(1)->paid_amount);
        $this->assertSame('800.00',RentalContractExtension::find(1)->payment_amount);
        $this->assertSame('partially_refunded',RentalContractExpiryReminder::find(1)->payment_status);
        $this->assertSame('2030-01-01',RentalContractInfo::find(1)->ingaz_to->toDateString());
    }
    public function test_refund_before_payment_notification_records_gross_receipt_and_net_balance(): void
    {
        DB::table('rental_contract_infos')->update(['paid_amount'=>0,'remaining_amount'=>1000,'moyasar_paid_at'=>null]);
        $this->finance->shouldReceive('recordPaymentForContract')->once()->withArgs(fn($contract,$amount,$method)=>$amount===1000.0 && $method==='moyasar')
            ->andReturnUsing(function($contract){$contract->info->update(['paid_amount'=>1000,'remaining_amount'=>0,'payment_status'=>'paid']);return ['receipt_bond'=>Bond::find(1)];});
        $this->gateway->shouldReceive('getInvoice')->andReturn($this->invoice(100000));
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->andReturn(Bond::find(1));
        $service=$this->service();$payment=$service->resolve('invoice-1');$service->synchronize($payment);$service->synchronize($payment);
        $this->assertSame('0.00',RentalContractInfo::find(1)->paid_amount);
        $this->assertEquals(1,DB::table('moyasar_contract_refunds')->count());
    }
    public function test_refund_voucher_reverses_original_receipt_accounts_and_branch(): void
    {
        $bonds=\Mockery::mock(\Nami\Finance\Services\BondService::class);
        $bonds->shouldReceive('lastCode')->once()->andReturn(2);
        $bonds->shouldReceive('storeSarfBond')->once()->withArgs(fn($data)=>$data['debit_account_id']===22 && $data['credit_account_id']===11 && $data['value']===200.0 && $data['branch_id']===3 && $data['created_by_id']===7 && $data['is_tax']===0)->andReturn(Bond::find(1));
        $years=\Mockery::mock(\Nami\Finance\Services\YearService::class);$years->shouldReceive('checkDateRange')->once()->andReturn(true);
        $finance=new RentalContractFinanceService(\Mockery::mock(\Nami\Finance\Services\ConstraintService::class),$years,$bonds);
        $finance->recordMoyasarRefund(\App\Models\RentalContract::find(1),200,'invoice-1',1);
        $this->assertSame(1,Bond::count());
    }

    public function test_app_payment_refunds_are_monotonic_and_keep_the_original_amount(): void
    {
        DB::table('orders')->insert(['id'=>1]);
        DB::table('rental_order_payments')->insert(['id'=>1]);
        $invoice=fn($refund)=>array_replace($this->invoice($refund),['id'=>'invoice-app']);
        $this->gateway->shouldReceive('getInvoice')->andReturn($invoice(20000),$invoice(0),$invoice(100000));
        $service=new \App\Services\RentalOrderPaymentService($this->gateway);
        $payment=\App\Models\RentalOrderPayment::find(1);
        $service->synchronize($payment);$service->synchronize($payment);
        $this->assertSame('partially_refunded',$payment->fresh()->status);
        $this->assertSame('200.00',$payment->fresh()->refunded_amount);
        $service->synchronize($payment);
        $this->assertSame('refunded',$payment->fresh()->status);
        $this->assertSame('1000.00',$payment->fresh()->amount);
    }

    public function test_shared_webhook_rejects_bad_secret_and_returns_retryable_accounting_failure(): void
    {
        $gateway=\Mockery::mock(MoyasarService::class);
        $gateway->shouldReceive('verifyWebhookSignature')->andReturn(false,true);
        $notifications=\Mockery::mock(\App\Services\MoyasarNotificationService::class);
        $notifications->shouldReceive('handle')->once()->andThrow(new \RuntimeException('Accounting unavailable'));
        $controller=new \App\Http\Controllers\ingaz\MoyasarWebhookController();
        $request=\Illuminate\Http\Request::create('/','POST',[],[],[],['CONTENT_TYPE'=>'application/json'],'{"data":{"invoice_id":"invoice-1"}}');
        $this->assertSame(403,$controller->webhook($request,$gateway,$notifications)->getStatusCode());
        $this->assertSame(503,$controller->webhook($request,$gateway,$notifications)->getStatusCode());
    }

    public function test_automated_bond_retains_explicit_creator_without_admin_session(): void
    {
        $bonds=\Mockery::mock(\Nami\Finance\Services\BondService::class)->makePartial();
        $years=\Mockery::mock(\Nami\Finance\Services\YearService::class);
        $years->shouldReceive('getActiveYear')->once()->andReturn((object)['id'=>1]);
        $property=new \ReflectionProperty(\Nami\Finance\Services\BondService::class,'yearService');
        $property->setAccessible(true);$property->setValue($bonds,$years);
        $bonds->shouldReceive('store')->once()->withArgs(fn($data)=>$data['created_by_id']===7 && $data['branch_id']===3)->andReturn(Bond::find(1));
        $bonds->storeBond(['created_by_id'=>7,'branch_id'=>3,'payment_type'=>\Nami\Finance\Enums\BondPaymentTypes::Cash->value]);
        $this->assertNull(auth('admin')->id());
    }

    public function test_new_moyasar_payment_uses_its_own_receipt_and_preserves_previous_cash(): void
    {
        DB::table('rental_contract_infos')->update(['contract_value'=>1400,'paid_amount'=>400,'remaining_amount'=>1000,'moyasar_paid_at'=>null]);
        DB::table('rental_contract_invoices')->update(['total_due'=>1400,'paid_amount'=>400,'remaining_amount'=>1000]);
        DB::table('fn_bonds')->insert(['id'=>2]);
        $this->finance->shouldReceive('recordPaymentForContract')->once()->withArgs(fn($c,$amount,$method)=>$amount===1000.0 && $method==='moyasar')
            ->andReturnUsing(function($contract){$contract->info->update(['paid_amount'=>1400,'remaining_amount'=>0,'payment_status'=>'paid']);return ['receipt_bond'=>Bond::find(2)];});
        $this->finance->shouldReceive('recordMoyasarRefund')->once()->withArgs(fn($c,$amount,$reference,$receipt)=>$receipt===2 && $amount===1000.0)->andReturn(Bond::find(2));
        $this->gateway->shouldReceive('getInvoice')->andReturn($this->invoice(100000));
        $service=$this->service();$payment=$service->register(RentalContractInfo::find(1),'invoice-1',1000);
        $service->synchronize($payment);
        $this->assertSame('400.00',RentalContractInfo::find(1)->paid_amount);
        $this->assertEquals(2,$payment->fresh()->receipt_bond_id);
    }
}
