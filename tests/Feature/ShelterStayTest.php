<?php

namespace Tests\Feature;

use App\Models\Contract;
use App\Models\ContractInfo;
use App\Services\ShelterStayService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ShelterStayTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'shelter_stay_test');
            $app['config']->set('database.connections.shelter_stay_test', [
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
        Schema::create('fn_confirm_settings', function (Blueprint $table) {
            $table->id(); $table->string('title'); $table->string('value'); $table->timestamps();
        });
        Schema::create('contracts', function (Blueprint $table) {
            $table->id(); $table->string('status'); $table->timestamps();
        });
        Schema::create('contract_infos', function (Blueprint $table) {
            $table->id(); $table->unsignedBigInteger('contract_id');
            $table->boolean('is_outside_shelter')->default(false);
            $table->dateTime('outside_shelter_at')->nullable();
            $table->string('outside_shelter_reason')->nullable();
            $table->date('maid_date_back')->nullable(); $table->timestamps();
        });
        (require database_path('migrations/2026_11_01_120000_create_shelter_stays_table.php'))->up();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        DB::purge('shelter_stay_test');
        parent::tearDown();
    }

    private function admission(): ContractInfo
    {
        DB::table('contracts')->insert(['id' => 1, 'status' => 'shelter']);
        return ContractInfo::create(['contract_id' => 1, 'maid_date_back' => '2026-10-22']);
    }

    public function test_multiple_stays_exclude_absences_and_include_current_stay(): void
    {
        Carbon::setTestNow('2026-10-22');
        $info = $this->admission();
        Carbon::setTestNow('2026-11-01');
        $info->update(['is_outside_shelter' => true, 'outside_shelter_at' => now(), 'outside_shelter_reason' => 'rental_delivery']);
        Carbon::setTestNow('2026-11-11');
        $info->update(['is_outside_shelter' => false, 'outside_shelter_at' => null]);
        Carbon::setTestNow('2026-11-21');
        $summary = app(ShelterStayService::class)->summary(Contract::findOrFail(1));
        $this->assertSame(20, $summary['total_days']);
        $this->assertCount(2, $summary['periods']);
        $this->assertTrue($summary['periods'][1]['ongoing']);
        $info->update(['is_outside_shelter' => true, 'outside_shelter_at' => now()]);
        Carbon::setTestNow('2026-12-01');
        $summary = app(ShelterStayService::class)->summary(Contract::findOrFail(1));
        $this->assertSame(20, $summary['total_days']);
        $this->assertFalse($summary['periods'][1]['ongoing']);
        $this->assertFalse($summary['incomplete']);
    }

    public function test_repeated_outside_updates_do_not_create_extra_stays(): void
    {
        Carbon::setTestNow('2026-11-01');
        $info = $this->admission();
        $info->update(['is_outside_shelter' => true, 'outside_shelter_at' => now()]);
        $info->update(['is_outside_shelter' => true, 'outside_shelter_reason' => 'vacation']);
        $this->assertSame(1, DB::table('shelter_stays')->count());
    }

    public function test_legacy_baseline_is_explicitly_marked_incomplete(): void
    {
        Carbon::setTestNow('2026-11-01');
        DB::table('contracts')->insert(['id' => 1, 'status' => 'shelter']);
        DB::table('contract_infos')->insert(['contract_id' => 1, 'maid_date_back' => '2026-10-22']);
        $summary = app(ShelterStayService::class)->summary(Contract::findOrFail(1));
        $this->assertTrue($summary['incomplete']);
        $this->assertSame(10, $summary['total_days']);
        $this->assertSame(0, DB::table('shelter_stays')->count());
        ContractInfo::first()->update(['is_outside_shelter' => true, 'outside_shelter_at' => now()]);
        $this->assertSame(1, DB::table('shelter_stays')->count());
        $this->assertTrue(app(ShelterStayService::class)->summary(Contract::findOrFail(1))['incomplete']);
    }

    public function test_leaving_shelter_status_closes_the_current_stay(): void
    {
        Carbon::setTestNow('2026-10-22');
        $this->admission();
        $this->mock(\App\Services\ingaz\RecruitmentStageNotifier::class)
            ->shouldReceive('stageChanged')->once();
        Carbon::setTestNow('2026-11-01');
        Contract::findOrFail(1)->update(['status' => 'completed']);
        Carbon::setTestNow('2026-11-11');
        $summary = app(ShelterStayService::class)->summary(Contract::findOrFail(1));
        $this->assertSame(10, $summary['total_days']);
        $this->assertFalse($summary['periods'][0]['ongoing']);
    }

    public function test_stays_partial_displays_total_and_current_stay(): void
    {
        Carbon::setTestNow('2026-11-01');
        $this->admission();
        $summary = app(ShelterStayService::class)->summary(Contract::findOrFail(1));
        $html = view('admin.ingaz.ShelterContract.partials.stays', ['shelterStays' => $summary])->render();
        $this->assertStringContainsString('10 يوم', $html);
        $this->assertStringContainsString('حتى اليوم', $html);
        $this->assertStringContainsString('2026-10-22', $html);
    }
}
