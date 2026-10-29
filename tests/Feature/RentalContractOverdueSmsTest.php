<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ingaz\User;
use App\Services\RentalContractOverdueSmsService;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\HourlyTestCase;

class RentalContractOverdueSmsTest extends HourlyTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.connections.overdue_sms' => ['driver'=>'sqlite', 'database'=>':memory:', 'prefix'=>''],
            'database.default'=>'overdue_sms']);
        Schema::create('new_settings', function (Blueprint $table) {
            $table->id(); $table->string('key'); $table->text('value');
        });
        DB::table('new_settings')->insert(['key'=>'communication_channels', 'value'=>json_encode([
            'sms'=>['provider'=>'msegat', 'url'=>'https://sms.example.test', 'username'=>'test',
                'api_key'=>'test', 'sender'=>'Test', 'gateway_status'=>'on'],
        ])]);
        Carbon::setTestNow('2026-10-29 15:30:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function contract(): RentalContract
    {
        $contract = new RentalContract(['contract_number'=>'RC-42', 'status'=>'active', 'user_id'=>1]);
        $contract->setRelation('info', new RentalContractInfo(['ingaz_to'=>'2026-10-26']));
        $contract->setRelation('user', new User(['name'=>'أحمد', 'phone'=>'512345678', 'phone_code'=>'+966']));
        return $contract;
    }

    public function test_repeated_sends_recalculate_days_and_dispatch_each_time(): void
    {
        $service = \Mockery::mock(RentalContractOverdueSmsService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('dispatch')->once()->withArgs(fn ($phone, $message, $context) =>
            $phone === '966512345678' && str_contains($message, 'RC-42') && str_contains($message, '3 يوم')
            && $context['source'] === 'rental_contract_overdue_sms'
        )->andReturn('{"code":"1"}');
        $service->shouldReceive('dispatch')->once()->withArgs(fn ($phone, $message) => str_contains($message, '5 يوم'))
            ->andReturn('{"code":"1"}');
        $contract = $this->contract();
        $service->send($contract);
        Carbon::setTestNow('2026-10-31 08:00:00');
        $service->send($contract);
    }

    public function test_today_future_and_closed_contracts_cannot_send(): void
    {
        $service = new RentalContractOverdueSmsService();
        foreach (['2026-10-29', '2026-10-30', null] as $date) {
            $contract = $this->contract();
            $contract->info->ingaz_to = $date;
            $this->assertSame(0, $service->daysOverdue($contract));
        }
        foreach (['completed', 'cancelled'] as $status) {
            $contract = $this->contract(); $contract->status = $status;
            $this->assertSame(0, $service->daysOverdue($contract));
        }
        $contract = $this->contract(); $contract->closed_at = now();
        $this->expectException(ValidationException::class);
        $service->send($contract);
    }

    public function test_provider_rejection_is_not_reported_as_success(): void
    {
        $service = \Mockery::mock(RentalContractOverdueSmsService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('dispatch')->once()->andReturn('{"code":"400"}');
        $this->expectException(ValidationException::class);
        $service->send($this->contract());
    }

    public function test_action_without_its_specific_permission_is_forbidden(): void
    {
        $route = app('router')->getRoutes()->getByName('RentContract.sendOverdueSms');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('permission:' . \App\PermKeys::RENT_CONTRACT_SEND_OVERDUE_SMS . ',admin', $route->gatherMiddleware());
        $admin = \Mockery::mock(\App\Models\auth\Admin::class)->makePartial();
        $admin->id = 1;
        $admin->shouldReceive('can')->with(\App\PermKeys::RENT_CONTRACT_SEND_OVERDUE_SMS)->andReturn(false);
        auth('admin')->setUser($admin);
        try {
            app(RentContractController::class)->sendOverdueSms(42, new RentalContractOverdueSmsService());
            $this->fail('Expected permission denial');
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
            $this->assertSame(403, $exception->getStatusCode());
        }
    }
}
