<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ReleaseOrphanedMaidReservationsTest extends TestCase
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
        Schema::create('maids', function (Blueprint $table) {
            $table->id();
            $table->string('passport_num');
            $table->string('status')->default('reserved');
            $table->boolean('available')->default(false);
            $table->boolean('is_show')->default(false);
            $table->date('available_at')->nullable();
            $table->text('back_out_reason')->nullable();
            $table->softDeletes();
            $table->timestamps();
        });
        foreach (['contracts', 'orders', 'rental_contracts', 'maid_unavailable_records', 'maid_status_change_requests', 'transfer_service_contracts'] as $name) {
            Schema::create($name, function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('maid_id');
                $table->string('status')->nullable();
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

    public function test_preview_apply_and_repeat_preserve_other_fields(): void
    {
        $id = DB::table('maids')->insertGetId(['passport_num' => 'TEST1', 'available_at' => '2030-01-01']);
        $this->artisan('maids:release-orphaned-reservations')->expectsOutput('Eligible: 1')->assertExitCode(0);
        $this->assertSame('reserved', DB::table('maids')->where('id', $id)->value('status'));
        $this->artisan('maids:release-orphaned-reservations', ['--apply' => true])->expectsOutput('Repaired: 1')->assertExitCode(0);
        $maid = DB::table('maids')->where('id', $id)->first();
        $this->assertSame('available', $maid->status);
        $this->assertEquals(1, $maid->available);
        $this->assertEquals(0, $maid->is_show);
        $this->assertSame('2030-01-01', $maid->available_at);
        $this->artisan('maids:release-orphaned-reservations', ['--apply' => true])->expectsOutput('Repaired: 0')->assertExitCode(0);
    }

    public function test_assignment_history_and_unavailability_prevent_release(): void
    {
        foreach (['contracts', 'orders', 'rental_contracts', 'maid_unavailable_records', 'maid_status_change_requests', 'transfer_service_contracts'] as $name) {
            $id = DB::table('maids')->insertGetId(['passport_num' => $name]);
            DB::table($name)->insert(['maid_id' => $id, 'status' => 'completed', 'deleted_at' => now(), 'branch_id' => 99]);
        }
        DB::table('maids')->insert([
            ['passport_num' => 'reason', 'back_out_reason' => 'Manual hold', 'deleted_at' => null, 'status' => 'reserved'],
            ['passport_num' => 'deleted', 'back_out_reason' => null, 'deleted_at' => now(), 'status' => 'reserved'],
            ['passport_num' => 'backout', 'back_out_reason' => null, 'deleted_at' => null, 'status' => 'backout'],
        ]);
        $this->artisan('maids:release-orphaned-reservations', ['--apply' => true])->expectsOutput('Repaired: 0')->assertExitCode(0);
        $this->assertSame(0, DB::table('maids')->where('available', true)->count());
    }

    public function test_passport_filter_limits_repairs(): void
    {
        DB::table('maids')->insert([['passport_num' => 'ONE'], ['passport_num' => 'TWO']]);
        $this->artisan('maids:release-orphaned-reservations', ['--apply' => true, '--passport' => ['ONE']])
            ->expectsOutput('Repaired: 1')->assertExitCode(0);
        $this->assertSame('reserved', DB::table('maids')->where('passport_num', 'TWO')->value('status'));
    }
}
