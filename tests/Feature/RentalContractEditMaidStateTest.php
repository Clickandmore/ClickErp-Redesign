<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\RentContractController;
use App\Models\RentalContract;
use App\Services\RentalShelterPresenceService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use ReflectionMethod;
use Tests\TestCase;

class RentalContractEditMaidStateTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'rental_edit_test');
            $app['config']->set('database.connections.rental_edit_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['activitylog.enabled' => false]);
        $this->mock(RentalShelterPresenceService::class)
            ->shouldReceive('returnReplacedMaidInside')->andReturn(true)->byDefault();
        Schema::create('maids', function (Blueprint $table) {
            $table->id();
            $table->string('status');
            $table->boolean('available');
            $table->boolean('is_show')->default(true);
            $table->date('available_at')->nullable();
            $table->boolean('has_driving_license')->nullable();
            $table->string('driving_license_file')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        Schema::create('maid_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->string('locale');
        });
        foreach (['rental_contracts', 'contracts', 'orders'] as $name) {
            Schema::create($name, function (Blueprint $table) use ($name) {
                $table->id();
                if ($name === 'rental_contracts') $table->boolean('freeze_releases_maid')->default(false);
                $table->unsignedBigInteger('maid_id')->nullable();
                $table->string('status');
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('rental_edit_test');
        parent::tearDown();
    }

    private function maid(string $status = 'available', bool $available = true): int
    {
        return DB::table('maids')->insertGetId([
            'status' => $status, 'available' => $available, 'available_at' => '2026-09-01',
        ]);
    }

    private function contract(int $maidId, string $status): RentalContract
    {
        $id = DB::table('rental_contracts')->insertGetId(['maid_id' => $maidId, 'status' => $status]);
        return RentalContract::query()->setEagerLoads([])->findOrFail($id);
    }

    private function syncAfterEdit(RentalContract $contract, ?int $oldMaidId): void
    {
        $method = new ReflectionMethod(RentContractController::class, 'syncMaidBookingStateAfterContractEdit');
        $method->setAccessible(true);
        DB::transaction(fn () => $method->invoke(app(RentContractController::class), $contract, $oldMaidId));
    }

    public function test_editing_completed_or_cancelled_contract_keeps_released_maid_unchanged(): void
    {
        foreach (['completed', 'cancelled'] as $status) {
            $id = $this->maid();
            $before = DB::table('maids')->find($id);
            $this->syncAfterEdit($this->contract($id, $status), $id);
            $this->assertEquals($before, DB::table('maids')->find($id));
        }
    }

    public function test_editing_same_assignment_preserves_current_maid_state(): void
    {
        foreach (['available' => true, 'contracted' => false, 'backout' => false, 'in_accommodation' => false] as $status => $available) {
            $id = $this->maid($status, $available);
            $before = DB::table('maids')->find($id);
            $this->syncAfterEdit($this->contract($id, 'active'), $id);
            $this->assertEquals($before, DB::table('maids')->find($id));
        }
    }

    public function test_changing_maid_on_historical_contract_does_not_change_either_maid(): void
    {
        foreach (['completed', 'cancelled'] as $status) {
            $oldId = $this->maid('backout', false);
            $newId = $this->maid();
            $before = DB::table('maids')->orderBy('id')->get();
            $this->syncAfterEdit($this->contract($newId, $status), $oldId);
            $this->assertEquals($before, DB::table('maids')->orderBy('id')->get());
        }
    }

    public function test_reassigning_active_or_frozen_contract_releases_old_maid_and_books_new_maid(): void
    {
        foreach (['active', 'frozen'] as $status) {
            $oldId = $this->maid('contracted', false);
            $newId = $this->maid();
            $this->syncAfterEdit($this->contract($newId, $status), $oldId);
            $this->assertSame('available', DB::table('maids')->find($oldId)->status);
            $this->assertEquals(1, DB::table('maids')->find($oldId)->available);
            $this->assertSame('contracted', DB::table('maids')->find($newId)->status);
            $this->assertEquals(0, DB::table('maids')->find($newId)->available);
        }
    }

    public function test_reassignment_respects_other_commitments_of_old_maid(): void
    {
        foreach (['rental_contracts' => ['active', 'contracted'], 'orders' => ['new', 'reserved']] as $table => [$status, $expected]) {
            $oldId = $this->maid('contracted', false);
            DB::table($table)->insert(['maid_id' => $oldId, 'status' => $status]);
            $newId = $this->maid();
            $this->syncAfterEdit($this->contract($newId, 'active'), $oldId);
            $this->assertSame($expected, DB::table('maids')->find($oldId)->status);
            $this->assertEquals(0, DB::table('maids')->find($oldId)->available);
        }
    }

    public function test_editing_assignment_returns_old_maid_to_shelter_after_releasing_her(): void
    {
        $oldId = $this->maid('contracted', false);
        $newId = $this->maid();
        $contract = $this->contract($newId, 'active');

        $this->mock(RentalShelterPresenceService::class)
            ->shouldReceive('returnReplacedMaidInside')
            ->once()
            ->with($oldId, $contract)
            ->andReturnUsing(function () use ($oldId, $newId, $contract) {
                $this->assertSame('available', DB::table('maids')->find($oldId)->status);
                $this->assertEquals($newId, DB::table('rental_contracts')->find($contract->id)->maid_id);

                return true;
            });

        $this->syncAfterEdit($contract, $oldId);
    }

    public function test_reassignment_does_not_release_a_backed_out_maid(): void
    {
        $oldId = $this->maid('backout', false);
        $newId = $this->maid();
        $this->syncAfterEdit($this->contract($newId, 'active'), $oldId);
        $this->assertSame('backout', DB::table('maids')->find($oldId)->status);
        $this->assertEquals(0, DB::table('maids')->find($oldId)->available);
    }
}
