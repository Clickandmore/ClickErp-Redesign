<?php

namespace Tests\Unit;

use App\Models\TravelHistory;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TravelHistoryArrivalDateTimeTest extends TestCase
{
    public function test_it_does_not_append_a_second_time_to_legacy_datetime_values(): void
    {
        $trip = new TravelHistory([
            'arrival_date' => '2026-07-26 11:16:15',
            'arrival_time' => '11:16:15',
        ]);

        $this->assertSame(
            '2026-07-26 11:16:15',
            $trip->resolveArrivalDateTime()?->format('Y-m-d H:i:s')
        );
    }

    public function test_the_separate_arrival_time_overrides_an_embedded_legacy_time(): void
    {
        $trip = new TravelHistory([
            'arrival_date' => '2026-07-26 08:00:00',
            'arrival_time' => '11:16:15',
        ]);

        $this->assertSame(
            '2026-07-26 11:16:15',
            $trip->resolveArrivalDateTime()?->format('Y-m-d H:i:s')
        );
    }

    public function test_completion_normalizes_date_and_time_into_separate_fields(): void
    {
        $trip = new TravelHistory([
            'arrival_date' => '2026-07-26 11:16:15',
            'arrival_time' => '11:16:15',
        ]);

        $trip->normalizeArrivalDateAndTime(Carbon::parse('2026-07-27 14:30:00'));

        $this->assertSame('2026-07-26', $trip->arrival_date);
        $this->assertSame('11:16:15', $trip->arrival_time);
    }
}
