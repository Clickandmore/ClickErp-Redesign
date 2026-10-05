<?php

namespace Tests\Unit;

use App\Models\Contract;
use App\Services\ingaz\RecruitmentContractClientChangeService;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RecruitmentContractClientFinancialGuardTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'client_guard_test');
            $app['config']->set('database.connections.client_guard_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach (array_keys($this->financialTables()) as $table) {
            DB::statement('CREATE TABLE ' . $table . ' (id INTEGER PRIMARY KEY, contract_id INTEGER, deleted_at TEXT, contract_type TEXT, source_type TEXT, source_id INTEGER)');
        }
        DB::statement('CREATE TABLE fn_bonds (id INTEGER PRIMARY KEY, recruitment_contract_id INTEGER, statement TEXT, is_confirm INTEGER)');
        DB::statement('CREATE TABLE fn_constraints (id INTEGER PRIMARY KEY, statement TEXT, deleted_at TEXT)');
        DB::statement('CREATE TABLE fn_constraint_details (id INTEGER PRIMARY KEY, statement TEXT, deleted_at TEXT)');
    }

    private function blocker(): ?string
    {
        return app(RecruitmentContractClientChangeService::class)->blockingReason(new Contract(['id' => 514, 'user_id' => 564]));
    }

    public function test_contract_without_finance_records_is_eligible(): void
    {
        $this->assertNull($this->blocker());
    }

    /** @dataProvider financialTables */
    public function test_every_financial_link_blocks_even_archived_records(string $table): void
    {
        DB::table($table)->insert(['id' => 1, 'contract_id' => 514, 'deleted_at' => '2026-01-01']);
        $this->assertNotNull($this->blocker());
    }

    public function financialTables(): array
    {
        $tables = ['invoices', 'contract_payments', 'musaned_transfer_items', 'recruitment_contract_refunds',
            'recruitment_invoice_credit_notes', 'contract_return_settlements', 'financial_requests',
            'fn_procurement_documents', 'shelter_cost_entries', 'shelter_agent_cost_recoveries',
            'shelter_living_expense_allocations', 'commission_batch_items', 'arabia_recruitment_financial_references'];
        return array_combine($tables, array_map(fn ($table) => [$table], $tables));
    }

    public function test_draft_bond_without_invoice_blocks_change(): void
    {
        DB::table('fn_bonds')->insert(['id' => 1, 'recruitment_contract_id' => 514, 'is_confirm' => 0]);
        $this->assertNotNull($this->blocker());
    }

    /** @dataProvider statementTables */
    public function test_legacy_bonds_and_journals_with_no_fk_block_change(string $table): void
    {
        DB::table($table)->insert(['id' => 1, 'statement' => 'سند أو قيد للعقد #514 / الفاتورة #456']);
        $this->assertNotNull($this->blocker());
    }

    /** @dataProvider statementTables */
    public function test_similar_contract_numbers_do_not_block_change(string $table): void
    {
        DB::table($table)->insert(['id' => 1, 'statement' => 'سند أو قيد للعقد #5140 / الفاتورة #514']);
        $this->assertNull($this->blocker());
    }

    public function statementTables(): array
    {
        return [['fn_bonds'], ['fn_constraints'], ['fn_constraint_details']];
    }

    public function test_rental_financial_request_with_same_numeric_id_is_not_a_recruitment_transaction(): void
    {
        DB::table('financial_requests')->insert(['id' => 1, 'contract_id' => 514, 'contract_type' => 'rental']);
        $this->assertNull($this->blocker());
    }

    public function test_legacy_statement_with_space_after_hash_is_detected(): void
    {
        DB::table('fn_bonds')->insert(['id' => 1, 'statement' => 'سند قبض للعقد # 514 / الفاتورة #456']);
        $this->assertNotNull($this->blocker());
    }

    public function test_other_contract_financial_records_do_not_block_change(): void
    {
        foreach (array_keys($this->financialTables()) as $table) {
            DB::table($table)->insert(['id' => 1, 'contract_id' => 578]);
        }
        $this->assertNull($this->blocker());
    }

    public function test_commission_linked_only_by_source_id_is_detected(): void
    {
        DB::table('commission_batch_items')->insert(['id' => 1, 'source_type' => 'recruitment_contract', 'source_id' => 514]);
        $this->assertNotNull($this->blocker());
    }
}
