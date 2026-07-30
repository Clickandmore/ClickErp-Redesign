<?php

namespace Tests\Unit;

use App\Http\Controllers\ingaz\MaidController;
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
        $maid->setRelation('contracts', new EloquentCollection());
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

    private function invoke(string $method, mixed ...$arguments): mixed
    {
        $controller = app(MaidController::class);
        $reflection = new ReflectionMethod($controller, $method);
        $reflection->setAccessible(true);

        return $reflection->invoke($controller, ...$arguments);
    }
}
