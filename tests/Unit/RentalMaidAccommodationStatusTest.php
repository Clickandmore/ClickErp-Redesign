<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
use App\Models\Contract;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\Maid;
use App\Models\RentalContract;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use ReflectionMethod;
use Tests\TestCase;

class RentalMaidAccommodationStatusTest extends TestCase
{
    public function test_maid_inside_shelter_is_not_presented_as_reserved_from_stale_availability_flag(): void
    {
        $maid = new Maid([
            'status' => 'available',
            'available' => false,
            'maid_accommodation_type' => 2,
        ]);
        $shelterContract = new Contract(['status' => 'shelter']);
        $shelterContract->setRelation('info', null);
        $shelterContract->setRelation('sponsorshipTransfers', new EloquentCollection());
        $maid->setRelation('contracts', new EloquentCollection([$shelterContract]));
        $maid->setRelation('orders', new EloquentCollection());
        $maid->setRelation('rentalContracts', new EloquentCollection());

        $status = $this->invoke('resolveRentalMaidCvStatus', $maid);

        $this->assertSame('in_accommodation', $status);
    }

    public function test_active_rental_contract_still_takes_priority_over_shelter_location(): void
    {
        $maid = new Maid([
            'status' => 'contracted',
            'available' => false,
            'maid_accommodation_type' => 2,
        ]);
        $maid->setRelation('contracts', new EloquentCollection());
        $maid->setRelation('orders', new EloquentCollection());
        $maid->setRelation('rentalContracts', new EloquentCollection([
            new RentalContract(['status' => 'active']),
        ]));

        $status = $this->invoke('resolveRentalMaidCvStatus', $maid);

        $this->assertSame('active_rental_contract', $status);
    }

    public function test_active_sponsorship_transfer_prevents_ready_for_contract_status(): void
    {
        $transfer = new SponsorshipTransfer(['status' => 'trial_started']);
        $transfer->id = 28;

        $contract = new Contract(['status' => 'trial']);
        $contract->setRelation('sponsorshipTransfers', new EloquentCollection([$transfer]));

        $maid = new Maid([
            'status' => 'available',
            'available' => true,
            'maid_accommodation_type' => 2,
        ]);
        $maid->setRelation('contracts', new EloquentCollection([$contract]));
        $maid->setRelation('orders', new EloquentCollection());
        $maid->setRelation('rentalContracts', new EloquentCollection());

        $this->assertSame('active_sponsorship_transfer', $this->invoke('resolveRentalMaidCvStatus', $maid));
        $this->assertFalse($this->invoke('canCreateRentalContractForMaid', $maid));
    }

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $controller = app(MaidController::class);
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$arguments);
    }
}
