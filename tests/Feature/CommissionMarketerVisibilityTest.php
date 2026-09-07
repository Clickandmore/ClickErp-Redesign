<?php

namespace Tests\Feature;

use App\Models\auth\Admin;
use App\Services\commissions\CommissionOperationService;
use App\Services\commissions\CommissionProfileService;
use App\Services\commissions\LegacyCommissionMarketerResolver;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Mockery;
use Tests\TestCase;

class CommissionMarketerVisibilityTest extends TestCase
{
    use DatabaseTransactions;

    public function test_suspended_employees_are_excluded_from_the_marketers_commission_list(): void
    {
        $activeMarketer = Admin::factory()->create([
            'admin_type' => 'customer_service',
            'status' => 'active',
        ]);
        $suspendedMarketer = Admin::factory()->suspended()->create([
            'admin_type' => 'customer_service',
        ]);

        $operationService = Mockery::mock(CommissionOperationService::class);
        $operationService->shouldReceive('summary')
            ->once()
            ->with($activeMarketer->id)
            ->andReturn(['total' => 0]);

        $marketerResolver = Mockery::mock(LegacyCommissionMarketerResolver::class);
        $marketerResolver->shouldReceive('displayName')
            ->once()
            ->with(Mockery::on(fn (Admin $admin) => $admin->is($activeMarketer)))
            ->andReturn($activeMarketer->name);

        $marketers = (new CommissionProfileService($operationService, $marketerResolver))
            ->getMarketersWithBalance();

        $this->assertTrue($marketers->contains('id', $activeMarketer->id));
        $this->assertFalse($marketers->contains('id', $suspendedMarketer->id));
    }
}
