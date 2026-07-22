<?php

namespace Tests\Unit;

use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\ingaz\OrderController;
use App\Models\ingaz\Order;
use App\Support\AutomationSettings;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use ReflectionProperty;
use Tests\TestCase;

class OrderTimerTest extends TestCase
{
    private ReflectionProperty $settingsCache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->settingsCache = new ReflectionProperty(AutomationSettings::class, 'cache');
        $this->settingsCache->setAccessible(true);
        $this->settingsCache->setValue(null, [
            'order_new_deadline_hours' => 24,
            'order_under_process_deadline_hours' => 48,
            'order_urgent_threshold_hours' => 2,
        ]);
    }

    protected function tearDown(): void
    {
        $this->settingsCache->setValue(null, []);
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_new_legacy_order_derives_timer_when_deadline_is_missing(): void
    {
        Carbon::setTestNow('2026-07-22 12:00:00');

        $order = new Order([
            'status' => OrderEnum::New->value,
            'status_changed_at' => Carbon::parse('2026-07-22 11:00:00'),
            'deadline_at' => null,
        ]);

        $deadline = $order->getEffectiveDeadline();

        $this->assertNotNull($deadline);
        $this->assertSame(
            Carbon::parse('2026-07-22 11:00:00')->addHours($order->getDeadlineHours())->toDateTimeString(),
            $deadline->toDateTimeString()
        );
        $this->assertNotNull($order->getRemainingTime());
    }

    public function test_inactive_order_without_deadline_does_not_get_timer(): void
    {
        $order = new Order([
            'status' => OrderEnum::Completed->value,
            'status_changed_at' => Carbon::parse('2026-07-22 11:00:00'),
            'deadline_at' => null,
        ]);

        $this->assertNull($order->getEffectiveDeadline());
        $this->assertNull($order->getRemainingTime());
    }

    public function test_completed_order_timer_cell_is_empty(): void
    {
        $order = new Order([
            'status' => OrderEnum::Completed->value,
            'deadline_at' => null,
        ]);
        $order->setRelation('contracts', new Collection());

        $this->assertSame('', (new OrderController())->getTimerHtml($order));
    }
}
