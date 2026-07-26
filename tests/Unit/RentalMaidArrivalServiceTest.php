<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Models\ingaz\JobType;
use App\Models\ingaz\Maid;
use App\Services\ingaz\RentalMaidArrivalService;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Tests\TestCase;

class RentalMaidArrivalServiceTest extends TestCase
{
    public function test_rental_maid_is_selected_for_shelter_after_arrival(): void
    {
        $contract = $this->contractWithJobType(2, 'إيجار');

        $this->assertTrue(
            app(RentalMaidArrivalService::class)->shouldMoveToShelter($contract)
        );
    }

    public function test_recruitment_maid_is_not_selected_for_automatic_shelter_move(): void
    {
        $contract = $this->contractWithJobType(1, 'استقدام');

        $this->assertFalse(
            app(RentalMaidArrivalService::class)->shouldMoveToShelter($contract)
        );
    }

    public function test_rental_title_is_recognized_when_job_type_id_is_not_the_legacy_id(): void
    {
        $contract = $this->contractWithJobType(99, 'تأجير شهري');

        $this->assertTrue(
            app(RentalMaidArrivalService::class)->shouldMoveToShelter($contract)
        );
    }

    private function contractWithJobType(int $id, string $title): Contract
    {
        $maid = new Maid();
        $maid->setRelation('jobTypes', new EloquentCollection([
            new JobType(['id' => $id, 'title' => $title]),
        ]));

        $contract = new Contract();
        $contract->setRelation('maid', $maid);

        return $contract;
    }
}
