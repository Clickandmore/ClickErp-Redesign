<?php

namespace Tests\Unit;

use App\Http\Actions\ingaz\MaidAction;
use App\Models\ingaz\Maid;
use App\Support\CvOptionsSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Tests\TestCase;

class CvSiteSortOrderTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('cv_site_sort_order')->nullable();
        });
    }

    public function test_missing_or_invalid_setting_defaults_to_random(): void
    {
        $this->assertSame('random', CvOptionsSettings::siteSortOrder());
        DB::table('settings')->insert(['cv_site_sort_order' => 'invalid']);
        $this->assertSame('random', CvOptionsSettings::siteSortOrder());
        Schema::drop('settings');
        $this->assertSame('random', CvOptionsSettings::siteSortOrder());
    }

    public function test_listing_respects_saved_order_and_keeps_pinned_cvs_first(): void
    {
        DB::table('settings')->insert(['cv_site_sort_order' => 'random']);

        foreach (['oldest' => 'asc', 'newest' => 'desc', 'random' => 'desc'] as $order => $direction) {
            DB::table('settings')->update(['cv_site_sort_order' => $order]);
            $builder = Mockery::mock(Builder::class, [DB::query()])->makePartial();
            $builder->setModel(new Maid());
            $builder->shouldReceive('paginate')->once()->andReturnUsing(function () use ($builder) {
                return $builder->getQuery()->orders;
            });
            $action = Mockery::mock(MaidAction::class)->makePartial()->shouldAllowMockingProtectedMethods();
            $action->shouldReceive('availableQuery')->once()->andReturn($builder);

            $orders = $action->paginate(Request::create('/api/maids', 'GET', ['order_seed' => '123']));

            $this->assertSame(['column' => 'is_pinned', 'direction' => 'desc'], $orders[0]);
            if ($order === 'random') {
                $this->assertSame('MD5(CONCAT(maids.id, ?))', $orders[1]['sql']);
                $this->assertSame(['123'], $builder->getQuery()->getBindings());
            } else {
                $this->assertSame(['column' => 'maids.created_at', 'direction' => $direction], $orders[1]);
            }
            $this->assertSame(['column' => 'maids.id', 'direction' => $direction], $orders[2]);
        }
    }
}
