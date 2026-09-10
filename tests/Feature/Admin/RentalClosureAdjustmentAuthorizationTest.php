<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\auth\Admin;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class RentalClosureAdjustmentAuthorizationTest extends TestCase
{
    use DatabaseTransactions;

    public function test_daily_discount_fields_are_visible_to_a_rental_contract_viewer(): void
    {
        $admin = $this->adminWithPermissions([
            PermKeys::RENT_CONTRACT_VIEW,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('RentContract.index'))
            ->assertOk()
            ->assertSee('تطبيق خصم على تكلفة اليوم');
    }

    public function test_manual_adjustment_fields_are_rendered_for_an_authorized_admin(): void
    {
        $admin = $this->adminWithPermissions([
            PermKeys::RENT_CONTRACT_VIEW,
            PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE,
            PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE,
        ]);

        $this->actingAs($admin, 'admin')
            ->get(route('RentContract.index'))
            ->assertOk()
            ->assertSee('تطبيق خصم على تكلفة اليوم')
            ->assertSee('استخدام قيمة تسوية يدوية معتمدة');
    }

    public function test_control_permission_alone_cannot_override_the_calculated_adjustment(): void
    {
        $admin = $this->adminWithPermissions([
            PermKeys::RENT_CONTRACT_CONTROL,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('RentContract.closeAndMakeAvailable', 999999), $this->manualOverridePayload())
            ->assertForbidden();
    }

    public function test_override_permission_without_approval_cannot_execute_the_adjustment(): void
    {
        $admin = $this->adminWithPermissions([
            PermKeys::RENT_CONTRACT_CONTROL,
            PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE,
        ]);

        $this->actingAs($admin, 'admin')
            ->postJson(route('RentContract.closeAndMakeAvailable', 999999), $this->manualOverridePayload())
            ->assertForbidden();
    }

    private function adminWithPermissions(array $permissionNames): Admin
    {
        $admin = Admin::factory()->create();

        foreach ($permissionNames as $name) {
            $permission = Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $name, 'guard_name' => 'admin'],
                [
                    'display_name' => $name,
                    'group_name' => 'rent_contract',
                    'section_name' => 'rent_contracts',
                    'is_active' => true,
                ]
            );
            $admin->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin;
    }

    private function manualOverridePayload(): array
    {
        return [
            'closure_date' => '2026-08-24',
            'adjustment_type' => 'refund',
            'adjustment_amount' => 170,
            'use_manual_adjustment' => 1,
            'manual_adjustment_amount' => 150,
            'manual_adjustment_reason' => 'فرق معتمد من المحاسب الخارجي',
            'manual_adjustment_attachment' => UploadedFile::fake()->create('approval.pdf', 20, 'application/pdf'),
        ];
    }
}
