<?php

namespace Tests\Unit;

use Illuminate\Database\SQLiteConnection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Services\ConstraintService;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use ReflectionMethod;

class SupplierStatementWorkerTest extends TestCase
{
    public function test_worker_lookup_searches_linked_and_manual_workers_without_duplicate_movements(): void
    {
        $connection = new SQLiteConnection(new PDO('sqlite::memory:'));
        $previous = DB::getFacadeRoot();
        DB::swap($connection);
        try {
            foreach ([
                'CREATE TABLE fn_constraints (id INTEGER)',
                'CREATE TABLE contracts (id INTEGER, maid_id INTEGER, external_office_cost_constraint_id INTEGER)',
                'CREATE TABLE maids (id INTEGER, name TEXT, passport_num TEXT)',
                'CREATE TABLE contract_infos (contract_id INTEGER, manual_maid_name TEXT, manual_maid_passport_num TEXT)',
                'CREATE TABLE invoices (contract_id INTEGER, external_office_cost_constraint_id INTEGER)',
            ] as $sql) {
                $connection->statement($sql);
            }
            $connection->table('fn_constraints')->insert([['id' => 1], ['id' => 2], ['id' => 3]]);
            $connection->table('contracts')->insert([
                ['id' => 10, 'maid_id' => 5, 'external_office_cost_constraint_id' => 1],
                ['id' => 20, 'maid_id' => null, 'external_office_cost_constraint_id' => null],
            ]);
            $connection->table('maids')->insert(['id' => 5, 'name' => 'Mary', 'passport_num' => 'AB123']);
            $connection->table('contract_infos')->insert(['contract_id' => 20, 'manual_maid_name' => 'Anna', 'manual_maid_passport_num' => 'CD456']);
            $connection->table('invoices')->insert([
                ['contract_id' => 10, 'external_office_cost_constraint_id' => 1],
                ['contract_id' => 20, 'external_office_cost_constraint_id' => 2],
            ]);
            $service = (new ReflectionClass(ConstraintService::class))->newInstanceWithoutConstructor();
            $lookup = new ReflectionMethod(ConstraintService::class, 'supplierWorkerQuery');
            foreach (['Mary' => [1], '123' => [1], 'Anna' => [2], 'CD456' => [2], 'missing' => []] as $term => $expected) {
                $query = $lookup->invoke($service, $term);
                $this->assertEquals($expected, $connection->table('fn_constraints')->addWhereExistsQuery($query)->pluck('id')->all());
            }
            $query = $lookup->invoke($service);
            $rows = $connection->table('fn_constraints')->select('id')
                ->selectSub($query->selectRaw("COALESCE(NULLIF(statement_maid.name, ''), statement_info.manual_maid_name)")->limit(1), 'worker_name')->get();
            $this->assertCount(3, $rows);
            $this->assertSame('Mary', $rows[0]->worker_name);
            $this->assertSame('Anna', $rows[1]->worker_name);
            $this->assertNull($rows[2]->worker_name);
        } finally {
            DB::swap($previous);
        }
    }
}
