<?php

namespace Tests\Unit;

use App\Support\ClientSubscriptionSettings;
use Carbon\CarbonImmutable;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ClientSubscriptionSettingsTest extends TestCase
{
    protected function tearDown(): void
    {
        CarbonImmutable::setTestNow();
        parent::tearDown();
    }

    public function test_it_normalizes_subscription_values(): void
    {
        $settings = $this->invoke('normalize', [[
            'client_name' => '  شركة الاختبار  ',
            'status' => 'unknown',
            'work_started_at' => '2026-01-15',
            'subscription_started_at' => '',
            'subscription_ends_at' => 'not-a-date',
        ]]);

        $this->assertSame('شركة الاختبار', $settings['client_name']);
        $this->assertSame('active', $settings['status']);
        $this->assertSame('2026-01-15', $settings['work_started_at']);
        $this->assertNull($settings['subscription_started_at']);
        $this->assertNull($settings['subscription_ends_at']);
    }

    public function test_it_calculates_usage_and_remaining_days(): void
    {
        CarbonImmutable::setTestNow('2026-08-13 12:00:00');

        $summary = $this->invoke('withSummary', [[
            'work_started_at' => '2026-08-03',
            'subscription_ends_at' => '2026-09-02',
        ]]);

        $this->assertSame(10.0, (float) $summary['days_using_system']);
        $this->assertSame(20.0, (float) $summary['days_remaining']);
        $this->assertFalse($summary['is_expired']);
    }

    private function invoke(string $method, array $arguments): array
    {
        $reflection = new ReflectionMethod(ClientSubscriptionSettings::class, $method);
        $reflection->setAccessible(true);

        return $reflection->invokeArgs(null, $arguments);
    }
}
