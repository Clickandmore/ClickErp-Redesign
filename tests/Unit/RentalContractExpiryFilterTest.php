<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use Carbon\Carbon;
use Mockery;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class RentalContractExpiryFilterTest extends TestCase
{
    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Mockery::close();

        parent::tearDown();
    }

    public function test_expired_filter_excludes_contracts_ending_today(): void
    {
        $this->assertExpiryDateCondition('expired', '<');
    }

    public function test_not_expired_filter_includes_contracts_ending_today(): void
    {
        $this->assertExpiryDateCondition('not_expired', '>=');
    }

    private function assertExpiryDateCondition(string $filter, string $operator): void
    {
        Carbon::setTestNow(Carbon::parse('2026-07-21 14:30:00'));

        $infoQuery = Mockery::mock();
        $infoQuery->shouldReceive('whereNotNull')
            ->once()
            ->with('ingaz_to')
            ->andReturnSelf();
        $infoQuery->shouldReceive('whereDate')
            ->once()
            ->with('ingaz_to', $operator, Mockery::on(
                fn ($date) => $date instanceof Carbon && $date->isSameDay('2026-07-21')
            ))
            ->andReturnSelf();

        $query = Mockery::mock();
        $query->shouldReceive('whereNotIn')
            ->once()
            ->with('status', ['completed', 'cancelled'])
            ->andReturnSelf();
        $query->shouldReceive('whereHas')
            ->once()
            ->with('info', Mockery::on(function ($callback) use ($infoQuery) {
                $callback($infoQuery);

                return true;
            }))
            ->andReturnSelf();

        $controllerReflection = new ReflectionClass(RentContractController::class);
        $controller = $controllerReflection->newInstanceWithoutConstructor();
        $method = $controllerReflection->getMethod('applyRentalContractExpiryFilter');
        $method->setAccessible(true);
        $method->invoke($controller, $query, $filter);

        $this->assertTrue(true);
    }
}
