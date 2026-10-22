<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ContractController;
use App\Models\Contract;
use App\Models\TravelHistory;
use Illuminate\Support\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class ContractDeliveryFormArrivalDateTimeTest extends TestCase
{
    public function test_delivery_form_accepts_the_arrival_date_from_travel_history(): void
    {
        $trip = new TravelHistory([
            'arrival_date' => '2026-10-18',
            'arrival_time' => '14:30:00',
        ]);
        $method = new ReflectionMethod(ContractController::class, 'resolveArrivalDateTime');
        $method->setAccessible(true);

        $arrival = $method->invoke(app(ContractController::class), new Contract(), $trip);

        $this->assertInstanceOf(Carbon::class, $arrival);
        $this->assertSame('2026-10-18 14:30:00', $arrival->format('Y-m-d H:i:s'));
    }
}
