<?php

namespace Tests\Feature;

use App\Models\ingaz\Order;
use App\Services\ExternalBookingAvailabilityService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ExternalBookingAvailabilityTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'booking_availability_test');
            $app['config']->set('database.connections.booking_availability_test', [
                'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
            ]);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    public function test_reservations_cover_other_clients_and_branches_but_ignore_released_orders(): void
    {
        Schema::create('maids', function (Blueprint $table) {
            $table->id();
            $table->softDeletes();
        });
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->integer('maid_id');
            $table->integer('user_id');
            $table->integer('branch_id');
            $table->string('status')->nullable();
            $table->softDeletes();
        });
        DB::table('maids')->insert(['id' => 1]);
        $service = new ExternalBookingAvailabilityService;
        DB::transaction(function () use ($service) {
            $this->assertFalse($service->isReserved(1));
            DB::table('orders')->insert([
                'maid_id' => 1, 'user_id' => 99, 'branch_id' => 42, 'status' => 'new',
            ]);
            $this->assertTrue($service->isReserved(1));
            DB::table('orders')->update(['status' => null]);
            $this->assertTrue($service->isReserved(1));
            foreach (Order::nonBlockingMaidAssignmentStatuses() as $status) {
                DB::table('orders')->update(['status' => $status]);
                $this->assertFalse($service->isReserved(1));
            }
            DB::table('orders')->update(['status' => 'new', 'deleted_at' => now()]);
            $this->assertFalse($service->isReserved(1));
        });
    }
}
