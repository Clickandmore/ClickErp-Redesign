<?php

namespace Tests\Unit;

use App\Services\MoyasarService;
use Mockery;
use PHPUnit\Framework\TestCase;

class MoyasarServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_uses_the_payment_status_when_a_payment_exists(): void
    {
        $service = Mockery::mock(MoyasarService::class)->makePartial();
        $service->shouldReceive('getPayment')->once()->with('pay_1')->andReturn(['status' => 'paid']);
        $service->shouldNotReceive('getInvoice');

        $this->assertSame('paid', $service->verifyPaymentStatus('pay_1'));
    }

    public function test_it_falls_back_to_the_invoice_status(): void
    {
        $service = Mockery::mock(MoyasarService::class)->makePartial();
        $service->shouldReceive('getPayment')->once()->with('invoice_1')->andReturn(null);
        $service->shouldReceive('getInvoice')->once()->with('invoice_1')->andReturn(['status' => 'expired']);

        $this->assertSame('expired', $service->verifyPaymentStatus('invoice_1'));
    }
}
