<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{DB, Schema};
use Tests\HourlyTestCase as TestCase;

class HourlyRenewalMigrationTest extends TestCase
{
    /** @dataProvider columnStates */
    public function test_cleanup_preserves_valid_links_and_can_resume(string $column): void
    {
        config(['database.connections.renewal_migration' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''], 'database.default' => 'renewal_migration']);
        Schema::create('hourly_rental_orders', function (Blueprint $table) use ($column) {
            $table->id();
            $table->unsignedBigInteger($column)->nullable()->index();
        });
        DB::table('hourly_rental_orders')->insert([['id' => 1, $column => null], ['id' => 2, $column => 1]]);
        // More than one chunk, to catch skipped rows while the matching set shrinks.
        for ($id = 3; $id <= 503; $id++) {
            DB::table('hourly_rental_orders')->insert(['id' => $id, $column => 9999]);
        }
        $migration = require database_path('migrations/2026_09_23_000001_link_hourly_order_renewals.php');
        DB::enableQueryLog();
        $migration->up();
        $migration->up();
        $this->assertEquals(1, DB::table('hourly_rental_orders')->where('id', 2)->value('renewed_from_order_id'));
        $this->assertSame(502, DB::table('hourly_rental_orders')->whereNull('renewed_from_order_id')->count());
        foreach (DB::getQueryLog() as $query) {
            if (str_starts_with(strtolower($query['query']), 'update')) {
                $this->assertStringNotContainsString('select', strtolower($query['query']));
            }
        }
        DB::disableQueryLog();
    }

    public function columnStates(): array
    {
        return [['renewed_from_id'], ['renewed_from_order_id']];
    }
}
