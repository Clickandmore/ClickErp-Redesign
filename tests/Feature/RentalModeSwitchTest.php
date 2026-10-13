<?php

namespace Tests\Feature;

use App\Models\ingaz\Maid;
use App\Services\Rental\RentalModeSwitcher;
use App\Support\HourlyRentalSettings;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Illuminate\Validation\ValidationException;
use Tests\Feature\Concerns\{BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema};
use Tests\TestCase;

class RentalModeSwitchTest extends TestCase
{
    use BuildsHourlyCategorySchema, BuildsHourlyWorkerSchema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpHourlyCategorySchema();
        $this->buildWorkers();
        foreach ([1 => ['Ready regular', 'regular'], 2 => ['Hourly worker', 'hourly'], 3 => ['Rented regular', 'regular']] as $id => [$name, $mode]) {
            DB::table('maids')->insert(['id' => $id, 'name' => $name, 'rental_mode' => $mode, 'rental_source' => 'inside', 'arrival_date' => '2026-09-17', 'status' => 'available']);
            DB::table('maid_jobs')->insert(['maid_id' => $id, 'job_type_id' => 2]);
            DB::table('contracts')->insert(['id' => $id, 'maid_id' => $id, 'status' => 'shelter']);
            DB::table('contract_infos')->insert(['contract_id' => $id, 'shelter_id' => 1, 'is_outside_shelter' => false, 'wants_to_work' => 'yes']);
        }
        DB::table('rental_contracts')->insert(['maid_id' => 3, 'status' => 'active']);
    }

    protected function tearDown(): void
    {
        HourlyRentalSettings::flush();
        parent::tearDown();
    }

    public function test_only_sheltered_ready_worker_without_active_rental_converts_to_hourly(): void
    {
        $switcher = app(RentalModeSwitcher::class);
        $this->assertNull($switcher->hourlyBlocker(Maid::find(1)));
        $this->assertSame(__('rental_mode.errors.active_rental'), $switcher->hourlyBlocker(Maid::find(3)));
        $this->assertSame(__('rental_mode.errors.already_hourly'), $switcher->hourlyBlocker(Maid::find(2)));

        $maid = $switcher->toHourly(Maid::find(1));
        $this->assertSame('hourly', $maid->fresh()->rental_mode);
        $this->assertSame('HM-1', $maid->fresh()->hourly_username);

        $this->expectException(ValidationException::class);
        $switcher->toHourly(Maid::find(3));
    }

    public function test_hourly_worker_with_open_visit_cannot_return_to_regular(): void
    {
        // جدول طلبات مبسّط: الاختبار يخص حالة الطلب والزيارة فقط.
        Schema::dropIfExists('hourly_visit_workers');
        Schema::dropIfExists('hourly_visits');
        Schema::dropIfExists('hourly_rental_orders');
        Schema::create('hourly_visits', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('hourly_rental_order_id'); $t->string('status'); $t->dateTime('scheduled_start_at')->nullable(); $t->dateTime('scheduled_end_at')->nullable(); });
        Schema::create('hourly_visit_workers', function (Blueprint $t) { $t->id(); $t->unsignedBigInteger('hourly_visit_id'); $t->unsignedBigInteger('maid_id'); });
        Schema::create('hourly_rental_orders', function (Blueprint $t) { $t->id(); $t->string('order_number')->nullable(); $t->string('status'); });
        $orderId = DB::table('hourly_rental_orders')->insertGetId(['order_number' => 'HR-1', 'status' => 'confirmed']);
        $visitId = DB::table('hourly_visits')->insertGetId(['hourly_rental_order_id' => $orderId, 'status' => 'scheduled', 'scheduled_start_at' => now()->addDay(), 'scheduled_end_at' => now()->addDay()->addHours(4)]);
        DB::table('hourly_visit_workers')->insert(['hourly_visit_id' => $visitId, 'maid_id' => 2]);

        $switcher = app(RentalModeSwitcher::class);
        $this->assertNotNull($switcher->regularBlocker(Maid::find(2)));

        DB::table('hourly_visits')->update(['status' => 'completed']);
        $switcher->toRegular(Maid::find(2));
        $this->assertSame('regular', Maid::find(2)->rental_mode);
    }

    public function test_conversion_endpoints_and_public_listing_exclude_hourly(): void
    {
        $this->postJson(route('admin.hourly-maids.to-hourly', 3))->assertJsonValidationErrors('maid_id');
        $this->postJson(route('admin.hourly-maids.to-hourly', 1))->assertOk()->assertJsonPath('redirect', route('admin.hourly-maids.show', 1));
        $this->postJson(route('admin.hourly-maids.to-regular', 1))->assertOk();
        $this->assertSame('regular', Maid::find(1)->rental_mode);

        $this->assertEqualsCanonicalizing([1, 3], Maid::query()->notHourly()->pluck('id')->all());
        $this->assertTrue(Maid::find(2)->isHourly());
    }
}
