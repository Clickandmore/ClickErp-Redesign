<?php
namespace Tests\Unit;

use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\HourlyOrderService;
use Tests\TestCase;

/** Postpaid orders (collected at the visit) activate before payment; prepaid ones wait for the money. */
class HourlyOrderConfirmabilityTest extends TestCase
{
    private function order(array $attributes = []): HourlyRentalOrder
    {
        return (new HourlyRentalOrder)->forceFill($attributes + ['status' => 'pending_confirmation', 'payment_method' => 'cash',
            'total_amount' => 300, 'paid_amount' => 0, 'expires_at' => now()->addDays(30), 'package_snapshot' => []]);
    }

    public function test_every_postpaid_method_can_be_confirmed_without_payment(): void
    {
        foreach (HourlyOrderService::POSTPAID_METHODS as $method) {
            $this->assertTrue(HourlyOrderService::confirmable($this->order(['payment_method' => $method])), $method);
        }
        $this->assertContains('cash_and_card', HourlyOrderService::POSTPAID_METHODS);
    }

    public function test_prepaid_methods_and_policies_wait_for_payment_until_paid(): void
    {
        foreach (['bank_transfer', 'moyasar', 'edaat', 'cheque', 'customer_balance'] as $method) {
            $this->assertNotNull(HourlyOrderService::confirmBlocker($this->order(['payment_method' => $method])), $method);
            $this->assertTrue(HourlyOrderService::confirmable($this->order(['payment_method' => $method, 'paid_amount' => 300])), $method.' paid');
        }
        $installments = $this->order(['package_snapshot' => ['payment_policy' => 'installments', 'charge_now' => 100]]);
        $this->assertSame('يجب تحصيل المبلغ المطلوب مقدمًا قبل تفعيل الطلب.', HourlyOrderService::confirmBlocker($installments));
        $this->assertTrue(HourlyOrderService::confirmable($installments->forceFill(['paid_amount' => 100])));
    }

    public function test_only_open_pending_orders_are_confirmable(): void
    {
        $this->assertFalse(HourlyOrderService::confirmable($this->order(['status' => 'active'])));
        $this->assertFalse(HourlyOrderService::confirmable($this->order(['expires_at' => now()->subMinute()])));
    }
}
