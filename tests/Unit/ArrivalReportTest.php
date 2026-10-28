<?php
namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\TravelHistory;
use App\Services\ingaz\ReportExportService;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class ArrivalReportTest extends TestCase
{
    private function contract(string $status, ?string $date, ?TravelHistory $trip = null): Contract
    {
        $contract = new Contract(['status' => $status]);
        $contract->exists = true;
        $contract->setRelation('info', new ContractInfo(['arrival_date' => $date]));
        $contract->setRelation('maid', null);
        $contract->setRelation('travelHistory', $trip);
        return $contract;
    }

    public function test_arrivals_use_inclusive_arrival_dates_and_keep_missing_dates_separate(): void
    {
        $first = $this->contract('arrival', '2026-06-01');
        $last = $this->contract('completed', '2026-06-30 23:59:59');
        $missing = $this->contract('arrival', null);
        $scheduled = $this->contract('tazker', null, new TravelHistory([
            'status' => 'pending', 'arrival_date' => '2026-06-15',
        ]));
        $historical = $this->contract('back', null, new TravelHistory([
            'status' => 'completed', 'arrival_date' => '2026-06-10',
        ]));
        $outside = $this->contract('completed', '2026-07-01');
        $method = new ReflectionMethod(ReportExportService::class, 'arrivalBuckets');
        $method->setAccessible(true);
        [$arrived, $undated] = $method->invoke(new ReportExportService(),
            [$first, $last, $missing, $scheduled, $historical, $outside],
            Carbon::parse('2026-06-01')->startOfDay(), Carbon::parse('2026-06-30')->endOfDay());
        $this->assertSame([$first, $last, $historical], $arrived->pluck(0)->all());
        $this->assertSame([$missing], $undated->pluck(0)->all());
    }
}
