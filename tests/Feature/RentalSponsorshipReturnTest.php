<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\RentalSponsorshipReturn;
use App\Models\SponsorshipTransfer;
use App\Models\ingaz\Maid;
use App\Services\ingaz\RentalSponsorshipReturnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RentalSponsorshipReturnTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'company_return_test');
            $app['config']->set('database.connections.company_return_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
            $app['config']->set('activitylog.enabled', false);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'CREATE TABLE maids (id INTEGER PRIMARY KEY, status TEXT, available INTEGER, is_show INTEGER, available_at TEXT, maid_accommodation_type INTEGER, has_driving_license INTEGER, driving_license_file TEXT, deleted_at TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE contracts (id INTEGER PRIMARY KEY, maid_id INTEGER, user_id INTEGER, company_profile_id INTEGER, company_finance_account_id INTEGER, branch_id INTEGER, status TEXT, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE contract_infos (id INTEGER PRIMARY KEY, contract_id INTEGER, shelter_id INTEGER, maid_date_back TEXT, wants_to_work TEXT, is_available_for_instant INTEGER, is_outside_shelter INTEGER, outside_shelter_at TEXT, outside_shelter_reason TEXT, outside_shelter_reference_type TEXT, outside_shelter_reference_id INTEGER, outside_shelter_by INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE sponsorship_transfers (id INTEGER PRIMARY KEY, contract_id INTEGER, new_sponsor_id INTEGER, workflow_source TEXT, status TEXT, deleted_at TEXT, branch_id INTEGER, created_at TEXT, updated_at TEXT)',
            'CREATE TABLE company_profiles (id INTEGER PRIMARY KEY, name TEXT, account_id INTEGER)',
            'CREATE TABLE contract_escape_reports (id INTEGER PRIMARY KEY, contract_id INTEGER)',
            'CREATE TABLE rental_contracts (id INTEGER PRIMARY KEY, maid_id INTEGER, status TEXT, freeze_releases_maid INTEGER, deleted_at TEXT)',
            'CREATE TABLE orders (id INTEGER PRIMARY KEY, maid_id INTEGER, status TEXT, deleted_at TEXT)',
            'CREATE TABLE contract_status_logs (id INTEGER PRIMARY KEY, contract_id INTEGER, status TEXT, date TEXT, notes TEXT, created_at TEXT, updated_at TEXT)',
        ] as $sql) DB::statement($sql);
        (require database_path('migrations/2026_11_08_000001_create_rental_sponsorship_returns_table.php'))->up();
        DB::table('maids')->insert(['id' => 1, 'status' => 'contracted', 'available' => false, 'is_show' => false]);
        DB::table('contracts')->insert(['id' => 1, 'maid_id' => 1, 'user_id' => 40, 'branch_id' => 2, 'status' => 'completed']);
        DB::table('contract_infos')->insert(['id' => 1, 'contract_id' => 1, 'is_outside_shelter' => true]);
        DB::table('company_profiles')->insert(['id' => 1, 'name' => 'Company', 'account_id' => 100]);
        DB::table('sponsorship_transfers')->insert(['id' => 1, 'contract_id' => 1, 'new_sponsor_id' => 40, 'workflow_source' => 'rental', 'status' => 'transferred']);
    }

    protected function tearDown(): void
    {
        DB::purge('company_return_test');
        parent::tearDown();
    }

    private function createReturn(): RentalSponsorshipReturn
    {
        return app(RentalSponsorshipReturnService::class)->create(SponsorshipTransfer::findOrFail(1), [
            'shelter_id' => 3, 'request_date' => now()->toDateString(), 'notes' => 'Return test',
        ], 7);
    }

    public function test_full_operational_path_preserves_original_transfer_and_never_requires_finance_tables(): void
    {
        $original = DB::table('sponsorship_transfers')->first();
        $operation = $this->createReturn();
        $this->assertSame(2, $operation->branch_id);
        $service = app(RentalSponsorshipReturnService::class);
        foreach (['approved', 'contract_sent', 'contract_signed', 'received'] as $stage) {
            $operation = $service->advance($operation, $stage, 7);
            $this->assertSame(40, DB::table('contracts')->value('user_id'));
            $this->assertNull(DB::table('contracts')->value('company_profile_id'));
            $this->assertFalse((bool) DB::table('maids')->value('available'));
        }
        $this->assertFalse((bool) DB::table('contract_infos')->value('is_outside_shelter'));
        $this->assertSame(3, DB::table('contract_infos')->value('shelter_id'));
        $operation = $service->advance($operation, 'transferred', 8, null, 'signed.pdf');
        $this->assertSame('transferred', $operation->status);
        $this->assertNull(DB::table('contracts')->value('user_id'));
        $this->assertSame(1, DB::table('contracts')->value('company_profile_id'));
        $this->assertSame('shelter', DB::table('contracts')->value('status'));
        $this->assertTrue((bool) DB::table('maids')->value('available'));
        $this->assertFalse((bool) DB::table('maids')->value('is_show'));
        $this->assertSame((array) $original, (array) DB::table('sponsorship_transfers')->first());
        $this->assertCount(6, $operation->history);
        $this->assertSame('signed.pdf', $operation->history[5]['attachment']);
        $this->assertFalse(Schema::hasTable('fn_constraints'));
        $maid = Maid::without('translations')->findOrFail(1);
        $this->assertFalse($maid->hasUnfinishedCompanyReturn());
    }

    public function test_cannot_skip_steps_or_create_duplicate_and_failed_changes_roll_back(): void
    {
        $operation = $this->createReturn();
        foreach ([fn () => $this->createReturn(), fn () => app(RentalSponsorshipReturnService::class)->advance($operation, 'transferred', 7)] as $attempt) {
            try { $attempt(); $this->fail('Expected validation error'); } catch (ValidationException $e) { $this->assertArrayHasKey('return', $e->errors()); }
        }
        $this->assertSame(1, RentalSponsorshipReturn::count());
        $this->assertSame('pending', $operation->fresh()->status);
        $this->assertSame(40, DB::table('contracts')->value('user_id'));
    }

    public function test_cancellation_after_receipt_keeps_actual_location_and_sponsor_and_allows_retry(): void
    {
        $operation = $this->createReturn();
        $service = app(RentalSponsorshipReturnService::class);
        foreach (['approved', 'contract_sent', 'contract_signed', 'received'] as $stage) $operation = $service->advance($operation, $stage, 7);
        $service->advance($operation, 'cancelled', 7, 'Sponsor postponed');
        $this->assertFalse((bool) DB::table('contract_infos')->value('is_outside_shelter'));
        $this->assertSame(40, DB::table('contracts')->value('user_id'));
        $this->assertFalse((bool) DB::table('maids')->value('available'));
        $this->assertTrue(Maid::without('translations')->findOrFail(1)->hasUnfinishedCompanyReturn());
        $this->assertSame('pending', $this->createReturn()->status);
    }

    public function test_active_rental_or_newer_transfer_or_changed_sponsor_blocks_creation(): void
    {
        DB::table('rental_contracts')->insert(['id' => 1, 'maid_id' => 1, 'status' => 'active']);
        try { $this->createReturn(); $this->fail(); } catch (ValidationException $e) { $this->assertSame(0, RentalSponsorshipReturn::count()); }
        DB::table('rental_contracts')->delete();
        DB::table('sponsorship_transfers')->insert(['id' => 2, 'contract_id' => 1, 'new_sponsor_id' => 50, 'status' => 'pending']);
        try { $this->createReturn(); $this->fail(); } catch (ValidationException $e) { $this->assertSame(0, RentalSponsorshipReturn::count()); }
        DB::table('sponsorship_transfers')->where('id', 2)->delete();
        DB::table('contracts')->update(['user_id' => 50]);
        $this->expectException(ValidationException::class);
        $this->createReturn();
    }

    public function test_rental_booking_guard_rejects_pending_return_even_if_worker_flags_are_changed(): void
    {
        $this->createReturn();
        $maid = Maid::without('translations')->findOrFail(1);
        $maid->available = true;
        $maid->status = 'available';
        $this->expectException(ValidationException::class);
        app(\App\Services\Rental\RentalModeGuard::class)->assertMonthly($maid);
    }

    public function test_company_return_routes_require_transfer_control_permission(): void
    {
        foreach (['', '.store', '.advance', '.attachment'] as $suffix) {
            $name = 'SponsorshipTransfer.returnCompany' . $suffix;
            $this->assertNotNull(app('router')->getRoutes()->getByName($name));
            $this->assertContains(\App\PermKeys::SPONSOR_TRANSFER_CONTROL,
                \App\Support\AdminRoutePermissionMatrix::resolve($name));
        }
    }

    public function test_confirming_ownership_does_not_make_an_unready_worker_available(): void
    {
        $operation = $this->createReturn();
        $service = app(RentalSponsorshipReturnService::class);
        foreach (['approved', 'contract_sent', 'contract_signed', 'received'] as $stage) $operation = $service->advance($operation, $stage, 7);
        $service->advance($operation, 'transferred', 7, null, null, false);
        $this->assertSame(1, DB::table('contracts')->value('company_profile_id'));
        $this->assertFalse((bool) DB::table('maids')->value('available'));
        $this->assertSame('no', DB::table('contract_infos')->value('wants_to_work'));
    }

    public function test_changed_sponsor_after_creation_blocks_confirmation_without_partial_changes(): void
    {
        $operation = $this->createReturn();
        DB::table('contracts')->update(['user_id' => 50]);
        try {
            app(RentalSponsorshipReturnService::class)->advance($operation, 'approved', 7);
            $this->fail('Expected sponsor validation');
        } catch (ValidationException $e) {
            $this->assertSame('pending', $operation->fresh()->status);
            $this->assertCount(1, $operation->fresh()->history);
            $this->assertSame(50, DB::table('contracts')->value('user_id'));
        }
    }
}
