<?php

namespace Tests\Unit;

use App\Models\RentalOrderPayment;
use App\Services\MoyasarService;
use App\Services\RentalOrderPaymentService;
use Mockery;
use Tests\TestCase;

class RentalOrderPaymentServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_it_rejects_a_paid_payload_for_another_invoice(): void
    {
        $payment = $this->pendingPayment();
        $service = new RentalOrderPaymentService(Mockery::mock(MoyasarService::class));

        $result = $service->applyProviderPayload($payment, [
            'id' => 'pay_1',
            'invoice_id' => 'invoice_other',
            'status' => 'paid',
            'amount' => 12500,
            'currency' => 'SAR',
        ]);

        $this->assertSame($payment, $result);
        $this->assertSame(RentalOrderPayment::STATUS_PENDING, $payment->status);
    }

    public function test_it_rejects_a_paid_payload_with_an_incorrect_amount(): void
    {
        $payment = $this->pendingPayment();
        $service = new RentalOrderPaymentService(Mockery::mock(MoyasarService::class));

        $result = $service->applyProviderPayload($payment, [
            'id' => 'pay_1',
            'invoice_id' => 'invoice_1',
            'status' => 'paid',
            'amount' => 12499,
            'currency' => 'SAR',
        ]);

        $this->assertSame($payment, $result);
        $this->assertSame(RentalOrderPayment::STATUS_PENDING, $payment->status);
    }

    private function pendingPayment(): RentalOrderPayment
    {
        return new RentalOrderPayment([
            'id' => 10,
            'order_id' => 20,
            'provider_invoice_id' => 'invoice_1',
            'amount' => 125,
            'currency' => 'SAR',
            'status' => RentalOrderPayment::STATUS_PENDING,
        ]);
    }
}
