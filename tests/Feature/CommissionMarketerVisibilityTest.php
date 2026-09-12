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
    public function test_administrator_with_operations_can_be_configured_as_a_commission_marketer(): void
    {
        $admin = Admin::factory()->create(['admin_type' => 'super_admin']);
        \App\Models\commissions\CommissionBatch::create([
            'marketer_id' => $admin->id, 'total_requests' => 1, 'total_base_amount' => 100,
            'total_commission' => 10, 'status' => 'approved', 'created_by' => $admin->id,
        ]);
        $parent = \Nami\Finance\Models\Account::create([
            'name' => 'المسوقين', 'code' => 144, 'status' => 'creditor', 'level' => 1,
            'type' => \Nami\Finance\Enums\AccountTypesEnum::Marketers->value,
        ]);
        $account = \Nami\Finance\Models\Account::create([
            'name' => 'حساب مستقل للمشرف', 'code' => 14401, 'status' => 'creditor', 'level' => 2,
            'parent_id' => $parent->id, 'type' => \Nami\Finance\Enums\AccountTypesEnum::Marketer->value,
        ]);
        $this->actingAs($admin, 'admin');
        $controller = app(\App\Http\Controllers\commissions\CommissionSettingsController::class);
        $this->assertTrue($controller->index()->getData()['marketers']->contains('id', $admin->id));
        $this->assertTrue(app(CommissionProfileService::class)->getPaymentMarketersWithBalance()->contains('id', $admin->id));

        $controller->store(\Illuminate\Http\Request::create('/commissions/settings', 'POST', [
            'payable_accounts' => [$admin->id => $account->id],
        ]));
        $this->assertSame($account->id, (int) $admin->fresh()->commission_payable_account_id);
    }

}
