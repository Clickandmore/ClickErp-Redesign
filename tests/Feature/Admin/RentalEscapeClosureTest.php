<?php

namespace Tests\Feature\Admin;

use App\Models\{Branch, Contract, ContractInfo, Permission, RentalContract, RentalContractInfo, RentalEscapeReport};
use App\Models\auth\Admin;
use App\Models\ingaz\{Maid, User};
use App\PermKeys;
use App\Services\ingaz\RentalContractFinanceService;
use App\Services\Rental\RentalEscapeService;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\{DB, Schema};
use Nami\Finance\Models\Constraint;
use Tests\TestCase;

class RentalEscapeClosureTest extends TestCase
{
    use DatabaseTransactions;

    private RentalContract $rental;
    private Maid $maid;
    private Contract $shelter;

    public function createApplication()
    {
        $app = require __DIR__.'/../../../bootstrap/app.php';
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        if (DB::connection()->getDatabaseName() !== 'rafdain_testing') {
            throw new \RuntimeException('Rental escape tests require the dedicated test database.');
        }
        if (!Schema::hasTable('rental_escape_reports')) {
            (require database_path('migrations/2026_10_19_180000_create_rental_escape_reports_table.php'))->up();
        }
        if (!Schema::hasColumn('rental_escape_reports', 'closed_without_return_at')) {
            (require database_path('migrations/2026_10_19_190000_add_no_return_closure_to_rental_escape_reports.php'))->up();
        }
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $branch = Branch::factory()->main()->create();
        $admin = Admin::factory()->forBranch($branch)->create();
        foreach ([PermKeys::RENT_CONTRACT_CONTROL, PermKeys::RENT_CONTRACT_VIEW] as $name) {
            $admin->givePermissionTo(Permission::firstOrCreate(['name' => $name, 'guard_name' => 'admin']));
        }
        $this->actingAs($admin, 'admin');
        $client = User::withoutEvents(fn () => User::create(['name' => 'Rental escape test']));
        $this->maid = Maid::withoutEvents(fn () => Maid::create(['status' => 'contracted', 'available' => false, 'branch_id' => $branch->id]));
        $this->rental = RentalContract::withoutEvents(fn () => RentalContract::create([
            'user_id' => $client->id, 'maid_id' => $this->maid->id, 'branch_id' => $branch->id,
            'status' => 'active', 'contract_date' => '2026-08-01',
        ]));
        RentalContractInfo::withoutEvents(fn () => RentalContractInfo::create([
            'rental_contract_id' => $this->rental->id, 'contract_value' => 1500, 'monthly_cost' => 1500,
            'contract_duration_days' => 30, 'contract_duration_months' => 1,
            'paid_amount' => 1500, 'payment_status' => 'paid', 'ingaz_from' => '2026-08-01', 'ingaz_to' => '2026-08-31',
        ]));
        $this->shelter = Contract::withoutEvents(fn () => Contract::create(['user_id' => $client->id,
            'maid_id' => $this->maid->id, 'branch_id' => $branch->id, 'status' => 'shelter']));
        ContractInfo::withoutEvents(fn () => ContractInfo::create(['contract_id' => $this->shelter->id,
            'msaned_number' => '', 'is_outside_shelter' => true, 'outside_shelter_reason' => 'rental_delivery', 'wants_to_work' => 'yes']));
    }

    private function payload(): array
    {
        return ['closure_reason' => 'escape', 'escape_date' => '2026-08-04', 'closure_date' => '2026-08-04',
            'escape_notes' => 'Reported missing by customer', 'adjustment_amount' => 99999, 'send_closure_message' => false];
    }

    private function mockFinance(bool $fail = false): void
    {
        $finance = $this->mock(RentalContractFinanceService::class);
        $finance->shouldReceive('syncPaymentForContract')->andReturn([]);
        $finance->shouldReceive('recordClosureAdjustment')->once()->withArgs(fn ($contract, $adjustment) =>
            $contract->id === $this->rental->id && (float) $adjustment->amount === 1300.0)
            ->andReturn($fail ? null : new Constraint());
    }

    public function test_escape_closes_contract_without_returning_or_releasing_worker(): void
    {
        $this->mockFinance();
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertOk()->assertJsonPath('success', true);
        $this->assertSame('escape', $this->rental->fresh()->closure_type);
        $this->assertSame('cancelled', $this->rental->fresh()->status);
        $this->assertSame('escaped', $this->maid->fresh()->status);
        $this->assertFalse($this->maid->fresh()->available);
        $this->assertSame('escape_report', $this->shelter->info->fresh()->outside_shelter_reason);
        $report = RentalEscapeReport::where('rental_contract_id', $this->rental->id)->firstOrFail();
        $this->assertEquals($this->rental->branch_id, $report->branch_id);
        $this->get(route('RentContract.show', $this->rental->id))->assertOk()->assertSee('بلاغ هروب العاملة');
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertUnprocessable();
        $this->assertSame(1, RentalEscapeReport::where('rental_contract_id', $this->rental->id)->count());
    }

    public function test_financial_failure_rolls_back_contract_worker_and_report(): void
    {
        $this->mockFinance(true);
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertStatus(500);
        $this->assertSame('active', $this->rental->fresh()->status);
        $this->assertSame('contracted', $this->maid->fresh()->status);
        $this->assertFalse(RentalEscapeReport::where('rental_contract_id', $this->rental->id)->exists());
    }

    public function test_missing_notes_future_escape_and_settlement_before_escape_are_rejected(): void
    {
        foreach ([['escape_notes' => ''], ['escape_date' => now()->addDay()->toDateString()], ['closure_date' => '2026-08-03']] as $invalid) {
            $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), array_replace($this->payload(), $invalid))->assertUnprocessable();
        }
        $this->assertSame('active', $this->rental->fresh()->status);
    }

    public function test_return_requires_a_separate_readiness_review_and_preserves_closed_contract(): void
    {
        $this->mockFinance();
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertOk();
        $this->postJson(route('RentContract.escapeReady', $this->rental->id), ['review_notes' => 'Reviewed'])->assertUnprocessable();
        $this->post(route('RentContract.escapeReturn', $this->rental->id), ['return_date' => '2026-08-05', 'return_notes' => 'Received at shelter'])->assertRedirect();
        $this->assertSame('returned_pending_review', $this->maid->fresh()->status);
        $this->assertFalse($this->maid->fresh()->available);
        $this->post(route('RentContract.escapeReady', $this->rental->id), ['review_notes' => 'Ready after review'])->assertRedirect();
        $this->assertSame('available', $this->maid->fresh()->status);
        $this->assertTrue($this->maid->fresh()->available);
        $this->assertSame('escape', $this->rental->fresh()->closure_type);
        $this->assertSame('cancelled', $this->rental->fresh()->status);
    }

    public function test_ordinary_worker_edit_cannot_bypass_open_report(): void
    {
        $this->mockFinance();
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertOk();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->maid->fresh()->update(['status' => 'available', 'available' => true]);
    }

    public function test_non_controller_cannot_record_or_resolve_escape(): void
    {
        $this->actingAs(Admin::factory()->create(), 'admin');
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertForbidden();
        $this->postJson(route('RentContract.escapeReturn', $this->rental->id), ['return_date' => '2026-08-05', 'return_notes' => 'Received'])->assertForbidden();
        $this->postJson(route('RentContract.escapeCloseWithoutReturn', $this->rental->id), [])->assertForbidden();
    }

    public function test_close_without_return_preserves_absence_finance_and_blocks_reactivation(): void
    {
        $this->mockFinance();
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertOk();
        $before = $this->rental->fresh()->info->getAttributes();
        $storage = $this->mock(\App\Services\ManagedFileStorage::class);
        $storage->shouldReceive('uploadFile')->once()->andReturn('test/no-return.pdf');
        $this->post(route('RentContract.escapeCloseWithoutReturn', $this->rental->id), [
            'no_return_date' => '2026-08-06', 'no_return_reason' => 'Follow-up concluded without return',
            'no_return_attachment' => \Illuminate\Http\UploadedFile::fake()->create('document.pdf', 10, 'application/pdf'),
        ])->assertRedirect();
        $report = RentalEscapeReport::where('rental_contract_id', $this->rental->id)->firstOrFail();
        $this->assertNotNull($report->closed_without_return_at);
        $this->assertEquals(auth('admin')->id(), $report->closed_without_return_by);
        $this->assertNull($report->returned_at);
        $this->assertNull($report->ready_at);
        $this->assertFalse($this->maid->fresh()->available);
        $this->assertSame('escaped', $this->maid->fresh()->status);
        $this->assertTrue((bool) $this->shelter->info->fresh()->is_outside_shelter);
        $this->assertEquals($before, $this->rental->fresh()->info->getAttributes());
        $this->get(route('RentContract.show', $this->rental->id))->assertOk()->assertSee('مغلق دون عودة')->assertDontSee('id="noReturnDate"', false);
        $this->postJson(route('RentContract.escapeReturn', $this->rental->id), ['return_date' => '2026-08-07', 'return_notes' => 'Received'])->assertUnprocessable();
        $this->postJson(route('RentContract.escapeReady', $this->rental->id), ['review_notes' => 'Ready'])->assertUnprocessable();
        $this->expectException(\Illuminate\Validation\ValidationException::class);
        $this->maid->fresh()->update(['available' => true]);
    }

    public function test_no_return_closure_validates_details_and_rejects_returned_reports(): void
    {
        $this->mockFinance();
        $this->postJson(route('RentContract.closeAndMakeAvailable', $this->rental->id), $this->payload())->assertOk();
        $this->postJson(route('RentContract.escapeCloseWithoutReturn', $this->rental->id), [
            'no_return_date' => now()->addDay()->toDateString(), 'no_return_reason' => ' ',
        ])->assertUnprocessable()->assertJsonValidationErrors(['no_return_date', 'no_return_reason', 'no_return_attachment']);
        $service = app(RentalEscapeService::class);
        foreach (['before_escape', 'after_return', 'duplicate'] as $case) {
            $report = RentalEscapeReport::where('rental_contract_id', $this->rental->id)->firstOrFail();
            $report->update(['returned_at' => $case === 'after_return' ? '2026-08-05' : null,
                'closed_without_return_at' => $case === 'duplicate' ? now() : null]);
            try {
                $service->closeWithoutReturn($this->rental, ['no_return_date' => $case === 'before_escape' ? '2026-08-03' : '2026-08-06', 'no_return_reason' => 'Reason'], ['path' => 'test.pdf']);
                $this->fail('Invalid closure was accepted: '.$case);
            } catch (\Illuminate\Validation\ValidationException $e) {
                $this->assertNull($report->fresh()->no_return_reason);
            }
        }
    }
}
