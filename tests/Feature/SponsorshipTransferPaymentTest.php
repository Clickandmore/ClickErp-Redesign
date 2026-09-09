<?php

namespace Tests\Feature;

use App\Models\Permission;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

class SponsorshipTransferPaymentTest extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    public function test_control_permission_does_not_allow_adding_payments(): void
    {
        $admin = $this->globalAdmin();
        $this->grant($admin, PermKeys::SPONSOR_TRANSFER_CONTROL);
        $this->actingAsAdmin($admin)->post(route('SponsorshipTransfer.addPayment', 999999))->assertForbidden();
    }

    public function test_payment_permission_reaches_validation(): void
    {
        $admin = $this->globalAdmin();
        $this->grant($admin, PermKeys::SPONSOR_TRANSFER_ADD_PAYMENT);
        $this->actingAsAdmin($admin)->postJson(route('SponsorshipTransfer.addPayment', 999999))
            ->assertStatus(422)->assertJsonValidationErrors(['amount', 'payment_receipt']);
    }

    /** @dataProvider invalidPayments */
    public function test_invalid_payments_leave_balance_unchanged(float $amount, float $expected): void
    {
        $transfer = $this->makeTrialTransfer();
        try {
            app(SponsorshipTransferPaymentService::class)->addPayment($transfer, [
                'amount' => $amount, 'expected_paid_amount' => $expected,
            ]);
            $this->fail('Payment should have been rejected');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $this->assertSame('5000.00', $transfer->fresh()->paid_amount);
        $this->assertSame('7000.00', $transfer->fresh()->remaining_amount);
    }

    public static function invalidPayments(): array
    {
        return [[7001, 5000], [0, 5000], [-1, 5000], [1000, 4000]];
    }

    public function test_partial_then_final_payment_updates_balance_and_rejects_resubmission(): void
    {
        $transfer = $this->makeTrialTransfer();
        $transfer->update(['payment_status' => 'paid']);
        $service = \Mockery::mock(SponsorshipTransferPaymentService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('issueReceipt')->once()->withArgs(fn ($model, $account, $amount) => $model->id === $transfer->id && $amount === 1000.0)
            ->andReturn(['bond' => null, 'constraint' => null]);
        $service->shouldReceive('issueReceipt')->once()->withArgs(fn ($model, $account, $amount) => $model->id === $transfer->id && $amount === 6000.0)
            ->andReturn(['bond' => null, 'constraint' => null]);
        $data = ['amount' => 1000, 'expected_paid_amount' => 5000, 'receipt_account_id' => 1, 'payment_type' => 'cash', 'payment_date' => '2026-09-09'];
        $service->addPayment($transfer, $data);
        $this->assertSame('6000.00', $transfer->fresh()->paid_amount);
        $this->assertSame('6000.00', $transfer->fresh()->remaining_amount);
        $this->assertSame('partial', $transfer->fresh()->payment_status);
        try {
            $service->addPayment($transfer, $data);
            $this->fail('Duplicate submission must be rejected');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('amount', $exception->errors());
        }
        $data['amount'] = 6000;
        $data['expected_paid_amount'] = 6000;
        $service->addPayment($transfer, $data);
        $this->assertSame('12000.00', $transfer->fresh()->paid_amount);
        $this->assertSame('0.00', $transfer->fresh()->remaining_amount);
        $this->assertSame('paid', $transfer->fresh()->payment_status);
    }

    public function test_receipt_failure_does_not_change_balance(): void
    {
        $transfer = $this->makeTrialTransfer();
        $service = \Mockery::mock(SponsorshipTransferPaymentService::class)->makePartial()->shouldAllowMockingProtectedMethods();
        $service->shouldReceive('issueReceipt')->once()->andThrow(new \RuntimeException('Receipt failed'));
        try {
            $service->addPayment($transfer, ['amount' => 1000, 'expected_paid_amount' => 5000, 'receipt_account_id' => 1, 'payment_type' => 'cash', 'payment_date' => '2026-09-09']);
            $this->fail('Receipt failure must be propagated');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Receipt failed', $exception->getMessage());
        }
        $this->assertSame('5000.00', $transfer->fresh()->paid_amount);
        $this->assertSame('7000.00', $transfer->fresh()->remaining_amount);
    }

    public function test_button_requires_permission_and_remaining_balance_even_if_status_is_paid(): void
    {
        $admin = $this->globalAdmin();
        $this->actingAsAdmin($admin);
        $transfer = $this->makeTrialTransfer();
        $transfer->payment_status = 'paid';
        $render = fn () => view('admin.ingaz.SponsorshipTransfer.partials.add-payment', [
            'transfer' => $transfer, 'receiptAccounts' => collect(),
            'errors' => new \Illuminate\Support\ViewErrorBag(),
        ])->render();
        $this->assertStringNotContainsString('إضافة دفعة', $render());
        $this->grant($admin, PermKeys::SPONSOR_TRANSFER_ADD_PAYMENT);
        $this->assertStringContainsString('إضافة دفعة', $render());
        $transfer->remaining_amount = 0;
        $this->assertStringNotContainsString('إضافة دفعة', $render());
    }
    private function makeTrialTransfer(): SponsorshipTransfer
    {
        $suffix = str_replace('.', '', uniqid('', true));
        $nationalityId = DB::table('nationalities')->insertGetId(['created_at' => now(), 'updated_at' => now()]);
        $religionId = DB::table('religions')->insertGetId(['created_at' => now(), 'updated_at' => now()]);

        $maidId = DB::table('maids')->insertGetId([
            'name' => 'Receipt maid',
            'passport_num' => 'RECEIPT-' . $suffix,
            'nationality_id' => $nationalityId,
            'religion_id' => $religionId,
            'salary' => 1400,
            'cv_file' => '',
            'height' => '',
            'weight' => '',
            'status' => 'contracted',
            'available' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $oldSponsorId = $this->makeClient('Old sponsor', '50' . substr($suffix, -7), '1011223344');
        $newSponsorId = $this->makeClient('New sponsor', '55' . substr($suffix, -7), '1099887766');

        $contractId = DB::table('contracts')->insertGetId([
            'user_id' => $oldSponsorId,
            'maid_id' => $maidId,
            'status' => 'completed',
            'type' => 'recruitment',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return SponsorshipTransfer::create([
            'contract_id' => $contractId,
            'old_sponsor_id' => $oldSponsorId,
            'new_sponsor_id' => $newSponsorId,
            'trial_days' => 7,
            'trial_start_date' => now()->toDateString(),
            'trial_started_at' => now(),
            'contract_value' => 12000,
            'paid_amount' => 5000,
            'remaining_amount' => 7000,
            'status' => 'trial_started',
        ]);
    }

    private function makeClient(string $name, string $phone, string $nationalNumber): int
    {
        return DB::table('users')->insertGetId([
            'name' => $name,
            'phone_code' => '+966',
            'phone' => $phone,
            'national_num' => $nationalNumber,
            'password' => bcrypt('password'),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function grant($admin, string $permission): void
    {
        Permission::withoutGlobalScopes()->updateOrCreate(
            ['name' => $permission, 'guard_name' => 'admin'],
            [
                'display_name' => $permission,
                'group_name' => 'sponsor_transfer',
                'section_name' => 'operations_management',
                'is_active' => true,
            ]
        );

        $admin->givePermissionTo($permission);
    }
}
