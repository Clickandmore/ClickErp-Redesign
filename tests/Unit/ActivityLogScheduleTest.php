<?php

namespace Tests\Unit;

use App\Console\Kernel;
use Illuminate\Console\Scheduling\Schedule;
use Tests\TestCase;

class ActivityLogScheduleTest extends TestCase
{
    public function test_cleanup_is_not_scheduled_without_explicit_opt_in(): void
    {
        config(['activitylog.scheduled_cleanup_enabled' => false]);
        $this->assertCount(0, $this->cleanupEvents());
    }

    public function test_opt_in_schedules_daily_cleanup_with_overlap_protection(): void
    {
        config(['activitylog.scheduled_cleanup_enabled' => true]);
        $events = $this->cleanupEvents();
        $this->assertCount(1, $events);
        $this->assertSame('30 3 * * *', $events[0]->expression);
        $this->assertTrue($events[0]->withoutOverlapping);
    }

    private function cleanupEvents(): array
    {
        $schedule = new Schedule();
        $method = new \ReflectionMethod(Kernel::class, 'schedule');
        $method->setAccessible(true);
        $method->invoke(app(Kernel::class), $schedule);

        return array_values(array_filter($schedule->events(), fn ($event) =>
            str_contains((string) $event->command, 'activitylog:clean')
        ));
    }
}
