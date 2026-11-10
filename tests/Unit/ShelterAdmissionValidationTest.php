<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\ShelterContractController;
use App\Models\Contract;
use App\Models\ContractInfo;
use Tests\TestCase;

class ShelterAdmissionValidationTest extends TestCase
{
    private function details(?string $arrivalDate, string $status = 'arrival'): ?array
    {
        $contract = new Contract(['status' => $status]);
        $contract->setRelation('info', new ContractInfo(['arrival_date' => $arrivalDate]));
        $contract->setRelation('maid', null);
        $contract->setRelation('travelHistory', null);
        $method = new \ReflectionMethod(ShelterContractController::class, 'shelterIneligibilityDetails');
        $method->setAccessible(true);

        return $method->invoke(app(ShelterContractController::class), $contract);
    }

    public function test_missing_arrival_explains_that_no_actual_arrival_is_recorded(): void
    {
        $this->assertStringContainsString('لا يحتوي على تاريخ وصول فعلي', $this->details(null)['message']);
    }

    public function test_future_arrival_explains_the_date_and_why_admission_is_refused(): void
    {
        $date = now()->addDays(2)->toDateString();
        $message = $this->details($date)['message'];
        $this->assertStringContainsString($date, $message);
        $this->assertStringContainsString('قبل تاريخ الوصول', $message);
    }

    public function test_arrival_today_and_in_the_past_are_eligible(): void
    {
        $this->assertNull($this->details(now()->toDateString()));
        $this->assertNull($this->details(now()->subDays(2)->toDateString()));
    }

    public function test_ineligible_status_explains_the_current_status(): void
    {
        $details = $this->details(null, 'shelter');
        $this->assertSame('shelter', $details['status']);
        $this->assertStringContainsString('حالته الحالية', $details['message']);
    }
}