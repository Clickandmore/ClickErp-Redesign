<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\RentContractController;
use Carbon\Carbon;
use ReflectionMethod;
use Tests\TestCase;

class RentalContractExtendedEndDateTest extends TestCase
{
    public function test_edit_preserves_a_later_recorded_extension_end_date(): void
    {
        $endDate = $this->resolveEndDate('2026-07-20', 30, null, '2026-09-18');

        $this->assertSame('2026-09-18', $endDate->format('Y-m-d'));
    }

    public function test_edit_calculates_the_end_date_normally_without_extensions(): void
    {
        $endDate = $this->resolveEndDate('2026-07-20', 30, null, null);

        $this->assertSame('2026-08-19', $endDate->format('Y-m-d'));
    }

    public function test_a_later_base_end_date_is_not_shortened_by_an_older_extension(): void
    {
        $endDate = $this->resolveEndDate('2026-07-20', 90, null, '2026-09-18');

        $this->assertSame('2026-10-18', $endDate->format('Y-m-d'));
    }

    private function resolveEndDate(
        string $startDate,
        int $durationDays,
        ?string $requestedEndDate,
        ?string $latestExtensionEndDate
    ): Carbon {
        $method = new ReflectionMethod(RentContractController::class, 'resolveUpdatedContractEndDate');
        $method->setAccessible(true);

        return $method->invoke(
            app(RentContractController::class),
            $startDate,
            $durationDays,
            $requestedEndDate,
            $latestExtensionEndDate
        );
    }
}
