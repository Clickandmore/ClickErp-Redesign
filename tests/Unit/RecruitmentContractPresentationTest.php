<?php

namespace Tests\Unit;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\ingaz\RecruitmentContractController;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\TravelHistory;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use ReflectionMethod;
use Tests\TestCase;

class RecruitmentContractPresentationTest extends TestCase
{
    private RecruitmentContractController $controller;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = app(RecruitmentContractController::class);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    /** @test */
    public function application_duration_starts_when_the_contract_record_was_created_and_stops_on_arrival(): void
    {
        Carbon::setTestNow('2026-07-28 12:00:00');

        $contract = new Contract([
            'status' => ContractStatusEnum::Completed->value,
        ]);
        $contract->created_at = Carbon::parse('2026-07-01 15:30:00');
        $contract->setRelation('contract_status_logs', new Collection());
        $contract->setRelation('info', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'completed',
            'arrival_date' => '2026-07-11',
            'arrival_time' => '09:45:00',
        ]));

        $summary = $this->invoke('buildContractDurationSummary', $contract);

        $this->assertSame(10, $summary['total_days']);
        $this->assertSame('10 أيام', $summary['total_label']);
        $this->assertSame('2026-07-01', $summary['started_at_label']);
        $this->assertSame('2026-07-11', $summary['ended_at_label']);
        $this->assertTrue($summary['worker_arrived']);
    }

    /** @test */
    public function arrival_details_include_the_recorded_flight_information(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Arrival->value,
        ]);
        $contract->setRelation('info', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'completed',
            'arrival_date' => '2026-07-20',
            'arrival_time' => '21:10:00',
            'arrival_airport' => 'مطار الملك خالد',
            'flight_company' => 'الخطوط السعودية',
            'flight_number' => 'SV123',
        ]));

        $arrival = $this->invoke('resolveWorkerArrivalDetails', $contract);

        $this->assertTrue($arrival['has_arrived']);
        $this->assertSame('2026-07-20', $arrival['date_label']);
        $this->assertSame('21:10', $arrival['time_label']);
        $this->assertSame('مطار الملك خالد', $arrival['station']);
        $this->assertSame('الخطوط السعودية', $arrival['flight_company']);
        $this->assertSame('SV123', $arrival['flight_number']);
    }

    /** @test */
    public function completed_contract_uses_the_saved_contract_info_arrival_date_when_no_trip_is_loaded(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Completed->value,
        ]);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo([
            'arrival_date' => '2026-07-22',
        ]));
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);

        $arrival = $this->invoke('resolveWorkerArrivalDetails', $contract);

        $this->assertTrue($arrival['has_arrived']);
        $this->assertSame('2026-07-22', $arrival['date_label']);
    }

    private function invoke(string $method, Contract $contract): array
    {
        $reflection = new ReflectionMethod($this->controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, $contract);
    }
}
