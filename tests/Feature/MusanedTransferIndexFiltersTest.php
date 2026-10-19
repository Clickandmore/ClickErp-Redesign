<?php

namespace Tests\Feature;

use App\Http\Controllers\ingaz\MusanedTransferController;
use App\Models\MusanedTransfer;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MusanedTransferIndexFiltersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'musaned_index_test', 'database.connections.musaned_index_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        Schema::create('musaned_transfers', function (Blueprint $t) {
            $t->increments('id');
            $t->string('reference_number')->nullable();
            $t->text('notes')->nullable();
            $t->date('transfer_date');
            $t->integer('contracts_count')->default(0);
            $t->decimal('transferred_total', 12, 2)->default(0);
            $t->integer('bank_account_id')->nullable();
            $t->integer('created_by_id')->nullable();
        });
        Schema::create('musaned_transfer_items', function (Blueprint $t) {
            $t->increments('id');
            foreach (['musaned_transfer_id', 'contract_id', 'ingaz_delegation_id', 'invoice_id'] as $column) {
                $t->integer($column)->nullable();
            }
        });
        Schema::create('contracts', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('user_id')->nullable();
        });
        Schema::create('ingaz_delegations', function (Blueprint $t) {
            $t->increments('id');
            $t->integer('user_id')->nullable();
            $t->string('ingaz_number')->nullable();
            $t->string('msaned_contract_number')->nullable();
        });
        Schema::create('users', function (Blueprint $t) {
            $t->increments('id');
            $t->string('name')->nullable();
            $t->string('phone')->nullable();
            $t->timestamp('deleted_at')->nullable();
        });

        DB::table('users')->insert([['id' => 1, 'name' => 'سارة العتيبي', 'phone' => '0551112222'], ['id' => 2, 'name' => 'فهد', 'phone' => '0553334444']]);
        DB::table('contracts')->insert(['id' => 50, 'user_id' => 1]);
        DB::table('ingaz_delegations')->insert(['id' => 60, 'user_id' => 2, 'ingaz_number' => 'ING-777']);
        DB::table('musaned_transfers')->insert([
            ['id' => 1, 'reference_number' => 'REF-A', 'transfer_date' => '2026-09-05', 'contracts_count' => 1, 'transferred_total' => 900, 'bank_account_id' => 7, 'created_by_id' => 3],
            ['id' => 2, 'reference_number' => 'REF-B', 'transfer_date' => '2026-10-10', 'contracts_count' => 3, 'transferred_total' => 4500, 'bank_account_id' => 8, 'created_by_id' => 4],
        ]);
        DB::table('musaned_transfer_items')->insert([
            ['musaned_transfer_id' => 1, 'contract_id' => 50, 'ingaz_delegation_id' => null, 'invoice_id' => 500],
            ['musaned_transfer_id' => 2, 'contract_id' => null, 'ingaz_delegation_id' => 60, 'invoice_id' => 501],
        ]);
    }

    private function ids(array $params): array
    {
        $controller = app(MusanedTransferController::class);
        $filters = (fn () => $this->indexFilters(Request::create('/', 'GET', $params)))->call($controller);
        $query = MusanedTransfer::query()->withoutGlobalScopes();
        (fn () => $this->applyIndexFilters($query, $filters))->call($controller);

        return $query->orderBy('id')->pluck('id')->all();
    }

    public function test_filters_narrow_the_transfer_list(): void
    {
        $this->assertSame([1, 2], $this->ids([]));
        $this->assertSame([2], $this->ids(['date_from' => '2026-10-01', 'date_to' => '2026-10-31']));
        $this->assertSame([1], $this->ids(['bank_account_id' => 7]));
        $this->assertSame([2], $this->ids(['created_by_id' => 4]));
        $this->assertSame([1], $this->ids(['kind' => 'single']));
        $this->assertSame([2], $this->ids(['kind' => 'bulk']));
        $this->assertSame([1], $this->ids(['source' => 'contract']));
        $this->assertSame([2], $this->ids(['source' => 'delegation']));
        $this->assertSame([2], $this->ids(['amount_min' => 1000]));
        $this->assertSame([1], $this->ids(['amount_max' => 1000]));
    }

    public function test_search_matches_reference_customer_and_ingaz_number(): void
    {
        $this->assertSame([2], $this->ids(['q' => 'REF-B']));
        $this->assertSame([1], $this->ids(['q' => 'سارة']));
        $this->assertSame([2], $this->ids(['q' => '0553334444']));
        $this->assertSame([2], $this->ids(['q' => 'ING-777']));
        $this->assertSame([1], $this->ids(['q' => '50']));
        $this->assertSame([], $this->ids(['q' => 'لا يوجد']));
    }

    public function test_invalid_values_are_ignored(): void
    {
        $this->assertSame([1, 2], $this->ids(['date_from' => 'yesterday', 'kind' => 'other', 'amount_min' => 'abc']));
    }
}
