<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class MusanedBranchRepairTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'musaned_repair_test', 'database.connections.musaned_repair_test' => [
            'driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '',
        ]]);
        foreach (['branches', 'contracts', 'ingaz_delegations', 'invoices', 'musaned_transfers', 'musaned_transfer_items', 'fn_bonds', 'fn_constraints', 'fn_constraint_details'] as $table) {
            Schema::create($table, function (Blueprint $t) {
                $t->increments('id');
                foreach (['branch_id', 'contract_id', 'ingaz_delegation_id', 'invoice_id', 'musaned_transfer_id', 'settlement_constraint_id', 'bank_transfer_constraint_id', 'recruitment_contract_id', 'constraint_id', 'invoice_constraint_id'] as $column) {
                    $t->integer($column)->nullable();
                }
            });
        }
        DB::table('branches')->insert(['id' => 2]);
        DB::table('contracts')->insert(['id' => 10, 'branch_id' => 2]);
        DB::table('musaned_transfers')->insert(['id' => 100, 'settlement_constraint_id' => 20]);
        DB::table('musaned_transfer_items')->insert(['id' => 1, 'musaned_transfer_id' => 100, 'contract_id' => 10]);
        DB::table('fn_constraints')->insert(['id' => 20]);
        DB::table('fn_constraint_details')->insert(['id' => 30, 'constraint_id' => 20]);
    }

    public function test_preview_does_not_write_and_apply_is_idempotent(): void
    {
        $this->artisan('musaned:repair-branches', ['--database' => ':memory:'])->assertSuccessful();
        $this->assertNull(DB::table('musaned_transfers')->value('branch_id'));
        $filesBefore = glob(storage_path('app/musaned-branch-repairs/*')) ?: [];
        try {
            $this->artisan('musaned:repair-branches', ['--database' => ':memory:', '--apply' => true])->assertSuccessful();
            $this->assertEquals(2, DB::table('musaned_transfers')->value('branch_id'));
            $this->assertEquals(2, DB::table('fn_constraint_details')->value('branch_id'));
            $this->artisan('musaned:repair-branches', ['--database' => ':memory:', '--apply' => true])->expectsOutput('Records: 0; skipped transfers: 0.')->assertSuccessful();
        } finally {
            foreach (array_diff(glob(storage_path('app/musaned-branch-repairs/*')) ?: [], $filesBefore) as $file) {
                unlink($file);
            }
        }
    }

    public function test_conflicting_invoice_skips_the_whole_transfer(): void
    {
        DB::table('invoices')->insert(['id' => 40, 'branch_id' => 1, 'contract_id' => 10]);
        DB::table('musaned_transfer_items')->where('id', 1)->update(['invoice_id' => 40]);
        $this->artisan('musaned:repair-branches', ['--database' => ':memory:', '--apply' => true])->assertFailed();
        $this->assertNull(DB::table('musaned_transfers')->value('branch_id'));
        $this->assertNull(DB::table('fn_constraints')->value('branch_id'));
    }

    public function test_multiple_branches_skip_the_whole_transfer(): void
    {
        DB::table('contracts')->insert(['id' => 11, 'branch_id' => 1]);
        DB::table('musaned_transfer_items')->insert(['id' => 2, 'musaned_transfer_id' => 100, 'contract_id' => 11]);
        $this->artisan('musaned:repair-branches', ['--database' => ':memory:', '--apply' => true])->assertFailed();
        $this->assertNull(DB::table('musaned_transfers')->value('branch_id'));
    }

    public function test_wrong_database_is_rejected(): void
    {
        $this->artisan('musaned:repair-branches', ['--database' => 'hajar_live', '--apply' => true])->assertFailed();
        $this->assertNull(DB::table('musaned_transfers')->value('branch_id'));
    }
}
