<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderDeletionReservationTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'orphan_test');
            $app['config']->set('database.connections.orphan_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'orphan_test', 'database.connections.orphan_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        config(['activitylog.enabled' => false]);
        Schema::create('maids', function (Blueprint $table) {
            $table->id();
            $table->string('passport_num');
            $table->string('status')->default('reserved');
            $table->boolean('available')->default(false);
            $table->boolean('is_show')->default(false);
            $table->date('available_at')->nullable();
            $table->text('back_out_reason')->nullable();
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
        foreach (['contracts', 'orders', 'rental_contracts', 'maid_unavailable_records', 'maid_status_change_requests', 'transfer_service_contracts'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('maid_id');
                $table->string('status')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->unsignedBigInteger('branch_id')->nullable();
            });
        }
    }

    protected function tearDown(): void
    {
        DB::purge('orphan_test');
        parent::tearDown();
    }

    private function reservedOrder(): \App\Models\ingaz\Order
    {
        $maidId = DB::table('maids')->insertGetId(['passport_num' => 'BK524769']);
        $orderId = DB::table('orders')->insertGetId(['maid_id' => $maidId, 'status' => 'new']);
        return \App\Models\ingaz\Order::withoutGlobalScopes()->without('details')->findOrFail($orderId);
    }

    public function test_legacy_action_keeps_its_existing_release_and_thirty_day_delay(): void
    {
        Schema::create('order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id');
        });
        $order = $this->reservedOrder();
        DB::table('orders')->insert(['maid_id' => $order->maid_id, 'status' => 'new']);

        $action = new \App\Http\Actions\ingaz\OrderAction(new \App\Models\ingaz\Order());
        $this->assertTrue($action->delete($order->id));

        $maid = DB::table('maids')->find($order->maid_id);
        $this->assertSame('available', $maid->status);
        $this->assertEquals(1, $maid->available);
        $this->assertSame(now()->addDays(30)->toDateString(), \Illuminate\Support\Carbon::parse($maid->available_at)->toDateString());
    }

    public function test_direct_soft_delete_releases_the_maid(): void
    {
        $order = $this->reservedOrder();
        $order->delete();
        $maid = DB::table('maids')->find($order->maid_id);
        $this->assertSame('available', $maid->status);
        $this->assertEquals(1, $maid->available);
        $this->assertSame(now()->toDateString(), \Illuminate\Support\Carbon::parse($maid->available_at)->toDateString());
        $this->assertNotNull(DB::table('orders')->find($order->id)->deleted_at);
    }

    public function test_permanent_delete_releases_a_legacy_stale_reservation(): void
    {
        $order = $this->reservedOrder();
        DB::table('orders')->where('id', $order->id)->update(['deleted_at' => now()]);
        $order->forceDelete();
        $this->assertNull(DB::table('orders')->find($order->id));
        $this->assertEquals(1, DB::table('maids')->find($order->maid_id)->available);
    }

    public function test_other_active_commitments_keep_the_maid_reserved(): void
    {
        foreach (['orders', 'contracts', 'rental_contracts', 'transfer_service_contracts'] as $table) {
            $order = $this->reservedOrder();
            DB::table($table)->insert(['maid_id' => $order->maid_id, 'status' => 'new', 'branch_id' => 99]);
            $order->delete();
            $this->assertSame('reserved', DB::table('maids')->find($order->maid_id)->status, $table);
            $this->assertEquals(0, DB::table('maids')->find($order->maid_id)->available, $table);
        }
    }

    public function test_deleted_orders_do_not_block_release(): void
    {
        $order = $this->reservedOrder();
        DB::table('orders')->insert(['maid_id' => $order->maid_id, 'status' => 'new', 'deleted_at' => now()]);
        $order->delete();
        $this->assertEquals(1, DB::table('maids')->find($order->maid_id)->available);
    }

    public function test_deletion_does_not_release_a_deleted_or_unavailable_maid(): void
    {
        foreach ([['deleted_at' => now()], ['status' => 'backout']] as $attributes) {
            $order = $this->reservedOrder();
            DB::table('maids')->where('id', $order->maid_id)->update($attributes);
            $order->delete();
            $this->assertEquals(0, DB::table('maids')->find($order->maid_id)->available);
        }
    }
}
