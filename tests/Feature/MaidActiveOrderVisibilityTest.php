<?php

namespace Tests\Feature;

use App\Models\ingaz\Maid;
use App\Models\ingaz\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MaidActiveOrderVisibilityTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'visibility_test');
            $app['config']->set('database.connections.visibility_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('maids', function (Blueprint $table) {
            $table->id();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->string('status')->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->softDeletes();
        });
        DB::table('maids')->insert(['id' => 1]);
    }

    protected function tearDown(): void
    {
        DB::purge('visibility_test');
        parent::tearDown();
    }

    public function test_deleted_reservation_does_not_hide_maid_with_completed_orders(): void
    {
        foreach (['completed', 'completed', 'return'] as $status) {
            DB::table('orders')->insert(['maid_id' => 1, 'status' => $status]);
        }
        $orderId = DB::table('orders')->insertGetId([
            'maid_id' => 1, 'status' => 'new', 'deleted_at' => now(),
        ]);

        $this->assertTrue($this->isEligible());

        // Restoring the reservation must hide the maid again.
        DB::table('orders')->where('id', $orderId)->update(['deleted_at' => null]);
        $this->assertFalse($this->isEligible());
    }

    public function test_active_orders_still_block_even_when_a_global_scope_hides_their_branch(): void
    {
        Order::query();
        Order::addGlobalScope('test_branch', fn (Builder $query) => $query->where('branch_id', 1));

        try {
            DB::table('orders')->insert(['maid_id' => 1, 'status' => 'under_process', 'branch_id' => 2]);
            $this->assertFalse($this->isEligible());
        } finally {
            Order::clearBootedModels();
        }
    }

    public function test_null_status_blocks_only_until_order_is_deleted(): void
    {
        $id = DB::table('orders')->insertGetId(['maid_id' => 1, 'status' => null]);
        $this->assertFalse($this->isEligible());

        DB::table('orders')->where('id', $id)->update(['deleted_at' => now()]);
        $this->assertTrue($this->isEligible());
    }

    private function isEligible(): bool
    {
        return Maid::withoutGlobalScopes()->whereKey(1)->withoutActiveOrders()->exists();
    }
}
