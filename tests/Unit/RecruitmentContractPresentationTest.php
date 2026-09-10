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
    public function application_duration_starts_on_the_contract_date_and_stops_on_arrival(): void
    {
        Carbon::setTestNow('2026-07-28 12:00:00');

        $contract = new Contract([
            'status' => ContractStatusEnum::Completed->value,
            'contract_date' => '2026-07-03',
        ]);
        $contract->created_at = Carbon::parse('2026-07-01 15:30:00');
        $contract->setRelation('contract_status_logs', new Collection());
        $contract->setRelation('info', new ContractInfo([
            'contract_date' => '2026-07-05',
        ]));
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'completed',
            'arrival_date' => '2026-07-11',
            'arrival_time' => '09:45:00',
        ]));

        $summary = $this->invoke('buildContractDurationSummary', $contract);

        $this->assertSame(6, $summary['total_days']);
        $this->assertSame('6 أيام', $summary['total_label']);
        $this->assertSame('2026-07-05', $summary['started_at_label']);
        $this->assertSame('2026-07-11', $summary['ended_at_label']);
        $this->assertTrue($summary['worker_arrived']);
    }

    /** @test */
    public function application_duration_falls_back_to_the_contract_record_date_then_created_at(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::New->value,
            'contract_date' => '2026-07-03',
        ]);
        $contract->created_at = Carbon::parse('2026-07-01 15:30:00');
        $contract->setRelation('contract_status_logs', new Collection());
        $contract->setRelation('info', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);
        $contract->setRelation('travelHistory', null);

        $summary = $this->invoke('buildContractDurationSummary', $contract);

        $this->assertSame('2026-07-03', $summary['started_at_label']);
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

    /** @test */
    public function scheduled_trip_date_is_presented_as_expected_arrival_before_the_worker_arrives(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Tazker->value,
        ]);
        $contract->setRelation('travelHistory', new TravelHistory([
            'status' => 'active',
            'arrival_date' => '2026-08-15',
            'arrival_time' => '18:30:00',
        ]));
        $contract->setRelation('info', null);
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);

        $arrival = $this->invoke('resolveWorkerArrivalDetails', $contract);

        $this->assertFalse($arrival['has_arrived']);
        $this->assertSame('2026-08-15', $arrival['date_label']);
        $this->assertSame('18:30', $arrival['time_label']);
    }

    /** @test */
    public function legacy_contract_arrival_date_is_not_presented_as_expected_arrival_without_a_trip(): void
    {
        $contract = new Contract([
            'status' => ContractStatusEnum::Tazker->value,
        ]);
        $contract->setRelation('travelHistory', null);
        $contract->setRelation('info', new ContractInfo([
            'arrival_date' => '2026-08-10',
        ]));
        $contract->setRelation('maid', null);
        $contract->setRelation('externalOfficeDispatch', null);

        $arrival = $this->invoke('resolveWorkerArrivalDetails', $contract);

        $this->assertFalse($arrival['has_arrived']);
        $this->assertNull($arrival['date_label']);
        $this->assertNull($arrival['time_label']);
    }

    public function test_financial_documents_include_invoice_entry_and_each_receipt_with_its_entry(): void
    {
        $contract = new Contract(['status' => ContractStatusEnum::New->value]);
        $invoice = new \App\Models\ingaz\Invoice();
        $invoice->forceFill(['id' => 10, 'invoice_constraint_id' => 101]);
        $invoice->setRelation('financeSale', null);
        $invoice->setRelation('recruitmentCreditNotes', new Collection());
        $invoice->setRelation('musanedTransferItem', null);
        $contract->setRelation('invoice', $invoice);
        $contract->setRelation('recruitmentRefund', null);
        $contract->setRelation('externalOfficeDispatch', null);
        $bonds = collect([
            ['id' => 20, 'code' => 'R20', 'type' => 'qabd', 'constraint_id' => 102],
            ['id' => 21, 'code' => 'R21', 'type' => 'qabd', 'constraint_id' => null],
            ['id' => 22, 'code' => 'S22', 'type' => 'sarf', 'constraint_id' => 103],
        ])->map(fn ($attributes) => (new \Nami\Finance\Models\Bond())->forceFill($attributes));
        $contract->setRelation('financeBonds', new Collection($bonds));

        $documents = collect($this->invoke('collectRecruitmentContractDocuments', $contract));
        $items = collect($documents->firstWhere('key', 'financial-attachments')['items'])->keyBy('key');

        $this->assertSame(route('finance.add-constraint.show', 101), $items['invoice-constraint']['url']);
        $this->assertSame(route('finance.bonds-print.show', ['bonds_print' => 20, 'type' => 'qabd']), $items['receipt-bond-20']['url']);
        $this->assertSame(route('finance.add-constraint.show', 102), $items['receipt-bond-constraint-20']['url']);
        $this->assertTrue($items->has('receipt-bond-21'));
        $this->assertFalse($items->has('receipt-bond-constraint-21'));
        $this->assertFalse($items->has('receipt-bond-22'));
    }

    private function invoke(string $method, Contract $contract): array
    {
        $reflection = new ReflectionMethod($this->controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($this->controller, $contract);
    }
}
