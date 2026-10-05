<?php

namespace Tests\Feature\Admin;

use App\Models\Permission;
use App\Models\auth\Admin;
use App\PermKeys;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use App\Models\RentalContract;
use App\Models\RentalContractInfo;
use App\Models\ingaz\User;
use App\Services\ingaz\RentalContractFinanceService;
use Nami\Finance\Models\Constraint;
use App\Models\RentalContractInvoice;
use App\Services\ingaz\RentalContractLedgerService;
use App\Console\Commands\ReconcileRentalClosures;
use ReflectionMethod;

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
        $this->get(route('RentContract.index'))->assertDontSee('id="useCustomClosureDailyRates"', false);
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
        $this->get(route('RentContract.index'))->assertSee('تعديل تكلفة اليوم عند الإنهاء');
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

    public function test_control_permission_alone_cannot_change_the_daily_price(): void
    {
        $admin = $this->adminWithPermissions([PermKeys::RENT_CONTRACT_CONTROL]);
        $this->actingAs($admin, 'admin')
            ->postJson(route('RentContract.closeAndMakeAvailable', 999999), $this->customPricingPayload())
            ->assertForbidden();
        $this->getJson(route('RentContract.details', 999999) . '?use_custom_daily_rates=1&early_closure_daily_rate=100')
            ->assertForbidden();
    }

    public function test_daily_price_requires_approval_permission(): void
    {
        $admin = $this->adminWithPermissions([
            PermKeys::RENT_CONTRACT_CONTROL, PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE,
        ]);
        $this->actingAs($admin, 'admin')
            ->postJson(route('RentContract.closeAndMakeAvailable', 999999), $this->customPricingPayload())
            ->assertForbidden();
    }

    public function test_preview_recalculates_both_the_invoice_note_and_cash_refund(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $this->actingAs($admin, 'admin')->getJson(route('RentContract.details', $contract->id) . '?' . http_build_query([
            'closure_date' => '2026-08-04', 'use_custom_daily_rates' => 1, 'early_closure_daily_rate' => 100,
        ]))->assertOk()->assertJsonPath('contract.used_cost', 400)
            ->assertJsonPath('contract.document_value', 1100)
            ->assertJsonPath('contract.cash_refund_value', 1100);
        $this->assertNull($contract->info->fresh()->closure_pricing);
    }

    public function test_preview_rejects_an_early_price_after_the_contract_has_ended(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $this->actingAs($admin, 'admin')->getJson(route('RentContract.details', $contract->id) . '?' . http_build_query([
            'closure_date' => '2026-09-04', 'use_custom_daily_rates' => 1, 'early_closure_daily_rate' => 100,
        ]))->assertUnprocessable()->assertJsonValidationErrors('early_closure_daily_rate');
    }

    public function test_custom_price_is_persisted_and_passed_to_the_financial_posting(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $finance = $this->mock(RentalContractFinanceService::class);
        $finance->shouldReceive('syncPaymentForContract')->once()->andReturn([]);
        $finance->shouldReceive('recordClosureAdjustment')->once()->withArgs(function ($record, $adjustment) use ($contract) {
            return $record->id === $contract->id && $adjustment->type === 'expense'
                && (float) $adjustment->amount === 1100.0;
        })->andReturn(new Constraint());
        $this->actingAs($admin, 'admin')->postJson(route('RentContract.closeAndMakeAvailable', $contract->id),
            $this->customPricingPayload())->assertOk()->assertJsonPath('success', true);
        $info = $contract->info->fresh();
        $this->assertSame(100, $info->closure_pricing['early_closure_daily_rate_override']);
        $this->assertSame(400, $info->closure_pricing['earned_amount']);
        $this->assertSame($admin->id, $info->closure_pricing['approved_by']);
        $this->assertSame('0.00', $info->remaining_amount);
        $this->assertSame('cancelled', $contract->fresh()->status);
        // Repeated submission cannot post another closure document.
        $this->postJson(route('RentContract.closeAndMakeAvailable', $contract->id), $this->customPricingPayload())
            ->assertJsonPath('success', false);
        $this->assertSame(1, $contract->financialAdjustments()->count());
    }

    public function test_posting_failure_rolls_back_the_price_and_contract_closure(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $finance = $this->mock(RentalContractFinanceService::class);
        $finance->shouldReceive('syncPaymentForContract')->once()->andReturn([]);
        $finance->shouldReceive('recordClosureAdjustment')->once()->andThrow(new \RuntimeException('posting failed'));
        $this->actingAs($admin, 'admin')->postJson(route('RentContract.closeAndMakeAvailable', $contract->id),
            $this->customPricingPayload())->assertJsonPath('success', false);
        $this->assertSame('active', $contract->fresh()->status);
        $this->assertNull($contract->info->fresh()->closure_pricing);
        $this->assertSame(0, $contract->financialAdjustments()->count());
    }

    public function test_overdue_price_is_saved_as_a_debit_note_and_remaining_receivable(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $finance = $this->mock(RentalContractFinanceService::class);
        $finance->shouldReceive('syncPaymentForContract')->once()->andReturn([]);
        $finance->shouldReceive('recordClosureAdjustment')->once()->withArgs(function ($record, $adjustment) use ($contract) {
            return $record->id === $contract->id && $adjustment->type === 'income'
                && (float) $adjustment->amount === 400.0;
        })->andReturn(new Constraint());
        $this->actingAs($admin, 'admin')->postJson(route('RentContract.closeAndMakeAvailable', $contract->id), [
            'closure_date' => '2026-09-04', 'adjustment_amount' => 1,
            'use_custom_daily_rates' => 1, 'overdue_daily_rate' => 100,
            'custom_daily_rate_reason' => 'سعر معتمد للأيام بعد انتهاء العقد',
        ])->assertOk()->assertJsonPath('success', true);
        $info = $contract->info->fresh();
        $this->assertSame(1500, $info->closure_pricing['used_cost']);
        $this->assertSame(400, $info->closure_pricing['overdue_cost']);
        $this->assertSame(1900, $info->closure_pricing['earned_amount']);
        $this->assertSame('400.00', $info->remaining_amount);
        $this->assertSame('completed', $contract->fresh()->status);
    }

    public function test_custom_price_cannot_be_combined_with_a_daily_discount(): void
    {
        $admin = $this->pricingAdmin();
        $contract = $this->pricingContract();
        $payload = $this->customPricingPayload() + [
            'use_daily_discount' => 1, 'daily_discount_type' => 'fixed',
            'daily_discount_value' => 5, 'daily_discount_reason' => 'خصم',
        ];
        $this->actingAs($admin, 'admin')->postJson(route('RentContract.closeAndMakeAvailable', $contract->id), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('use_custom_daily_rates');
        $this->assertSame('active', $contract->fresh()->status);
    }

    public function test_custom_price_requires_a_non_blank_reason(): void
    {
        $admin = $this->pricingAdmin();
        $payload = $this->customPricingPayload();
        $payload['custom_daily_rate_reason'] = '   ';
        $this->actingAs($admin, 'admin')->postJson(route('RentContract.closeAndMakeAvailable', 999999), $payload)
            ->assertUnprocessable()->assertJsonValidationErrors('custom_daily_rate_reason');
    }

    public function test_reconciliation_keeps_the_approved_earned_amount(): void
    {
        $contract = $this->pricingContract();
        $contract->update(['status' => 'cancelled', 'closed_at' => '2026-08-04']);
        $contract->info->update(['closure_pricing' => [
            'closure_date' => '2026-08-04', 'earned_amount' => 400,
            'early_closure_daily_rate_override' => 100,
        ]]);
        $contract->setRelation('financeInvoice', new RentalContractInvoice([
            'invoice_constraint_id' => 999999, 'total_due' => 1500,
        ]));
        $ledger = \Mockery::mock(RentalContractLedgerService::class);
        $ledger->shouldReceive('balanceForContract')->once()->andReturn(-1100.0);
        $ledger->shouldReceive('confirmedReceiptsTotal')->once()->andReturn(1500.0);
        $method = new ReflectionMethod(ReconcileRentalClosures::class, 'picture');
        $method->setAccessible(true);
        $picture = $method->invoke(app(ReconcileRentalClosures::class), $contract, $ledger);
        $this->assertSame(400.0, $picture['earned']);
        $this->assertSame(-1100.0, $picture['expected_balance']);
        $this->assertSame(0.0, $picture['difference']);
        $this->assertSame('ok', $picture['classification']);
    }

    private function customPricingPayload(): array
    {
        return ['closure_date' => '2026-08-04', 'adjustment_amount' => 99999,
            'use_custom_daily_rates' => 1, 'early_closure_daily_rate' => 100,
            'custom_daily_rate_reason' => 'تسعير يومي معتمد للإنهاء المبكر'];
    }

    private function pricingAdmin(): Admin
    {
        return $this->adminWithPermissions([PermKeys::RENT_CONTRACT_CONTROL,
            PermKeys::RENT_CONTRACT_VIEW, PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_OVERRIDE,
            PermKeys::RENT_CONTRACT_CLOSURE_ADJUSTMENT_APPROVE]);
    }

    private function pricingContract(): RentalContract
    {
        $user = User::withoutEvents(fn () => User::create(['name' => 'عميل اختبار التسعير']));
        $contract = RentalContract::withoutEvents(fn () => RentalContract::create([
            'user_id' => $user->id, 'status' => 'active', 'contract_date' => '2026-08-01',
        ]));
        RentalContractInfo::withoutEvents(fn () => RentalContractInfo::create([
            'rental_contract_id' => $contract->id, 'contract_value' => 1500,
            'monthly_cost' => 1500, 'contract_duration_days' => 30, 'contract_duration_months' => 1,
            'paid_amount' => 1500, 'payment_status' => 'paid',
            'ingaz_from' => '2026-08-01', 'ingaz_to' => '2026-08-31',
        ]));
        return $contract;
    }
}
