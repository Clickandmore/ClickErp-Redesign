<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RestoreRecruitmentContractClientTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__ . '/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'client_repair_test');
            $app['config']->set('database.connections.client_repair_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        foreach ([
            'CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT, client_type TEXT, national_num TEXT, tax_number TEXT, account_id INTEGER, deleted_at TEXT, archived_company_profile_id INTEGER)',
            'CREATE TABLE contracts (id INTEGER PRIMARY KEY, user_id INTEGER, order_id INTEGER, company_profile_id INTEGER, updated_at TEXT)',
            'CREATE TABLE contract_infos (id INTEGER PRIMARY KEY, contract_id INTEGER)',
            'CREATE TABLE orders (id INTEGER PRIMARY KEY, user_id INTEGER, updated_at TEXT)',
            'CREATE TABLE invoices (id INTEGER PRIMARY KEY, contract_id INTEGER, client_id INTEGER, finance_sale_id INTEGER, invoice_constraint_id INTEGER, updated_at TEXT)',
            'CREATE TABLE fn_sales (id INTEGER PRIMARY KEY, client_id INTEGER, debit_client_id INTEGER, zatca_invoice_id INTEGER, constraint_id INTEGER, updated_at TEXT)',
            'CREATE TABLE fn_accounts (id INTEGER PRIMARY KEY, name TEXT)',
            'CREATE TABLE fn_clients (id INTEGER PRIMARY KEY, name TEXT, account_id INTEGER)',
            'CREATE TABLE fn_bonds (id INTEGER PRIMARY KEY, debit_account_id INTEGER, credit_account_id INTEGER, recruitment_contract_id INTEGER, constraint_id INTEGER, statement TEXT, value REAL, updated_at TEXT)',
            'CREATE TABLE fn_constraint_details (id INTEGER PRIMARY KEY, account_id INTEGER, constraint_id INTEGER, statement TEXT, debtor_value REAL, creditor_value REAL, updated_at TEXT)',
            'CREATE TABLE recruitment_contract_refunds (id INTEGER PRIMARY KEY, contract_id INTEGER)',
            'CREATE TABLE recruitment_invoice_credit_notes (id INTEGER PRIMARY KEY, contract_id INTEGER)',
            'CREATE TABLE activity_log (id INTEGER PRIMARY KEY AUTOINCREMENT, log_name TEXT, description TEXT, subject_type TEXT, subject_id INTEGER, event TEXT, causer_type TEXT, causer_id INTEGER, properties TEXT, batch_uuid TEXT, created_at TEXT, updated_at TEXT)',
        ] as $sql) DB::statement($sql);
        DB::table('users')->insert([
            ['id' => 564, 'name' => 'Bassam', 'client_type' => 'person', 'national_num' => 'BASSAM', 'account_id' => 6339],
            ['id' => 1140, 'name' => 'Noura', 'client_type' => 'person', 'national_num' => 'NOURA', 'account_id' => 6849],
        ]);
        DB::table('contracts')->insert([
            ['id' => 514, 'user_id' => 564, 'order_id' => 494],
            ['id' => 578, 'user_id' => 564, 'order_id' => 556],
            ['id' => 5140, 'user_id' => 564, 'order_id' => null],
        ]);
        DB::table('orders')->insert([['id' => 494, 'user_id' => 564], ['id' => 556, 'user_id' => 564]]);
        DB::table('invoices')->insert([
            ['id' => 456, 'contract_id' => 514, 'client_id' => null, 'finance_sale_id' => 451],
            ['id' => 504, 'contract_id' => 578, 'client_id' => null, 'finance_sale_id' => 490],
        ]);
        DB::table('fn_sales')->insert([
            ['id' => 451, 'client_id' => 447, 'debit_client_id' => 6339],
            ['id' => 490, 'client_id' => 447, 'debit_client_id' => 6339],
        ]);
        DB::table('fn_accounts')->insert([['id' => 6339, 'name' => 'Old account'], ['id' => 6849, 'name' => 'Noura']]);
        DB::table('fn_clients')->insert([['id' => 447, 'account_id' => 6339, 'name' => 'Old client'], ['id' => 754, 'account_id' => 6849, 'name' => 'Noura']]);
        foreach ([[510, 514, 2755], [550, 578, 2872], [551, 5140, 2873]] as [$id, $contractId, $constraintId]) {
            DB::table('fn_bonds')->insert(['id' => $id, 'debit_account_id' => 5464, 'credit_account_id' => 6339, 'constraint_id' => $constraintId, 'statement' => 'سند قبض للعقد #' . $contractId . ' / الفاتورة', 'value' => 100]);
            DB::table('fn_constraint_details')->insert(['id' => $id, 'account_id' => 6339, 'constraint_id' => $constraintId, 'statement' => 'قيد للعقد #' . $contractId . ' / الفاتورة', 'debtor_value' => 0, 'creditor_value' => 100]);
        }
    }

    private function commandOptions(array $overrides = []): array
    {
        return $overrides + ['contract' => 514, 'client' => 1140, '--database' => ':memory:', '--from-client' => 564, '--identity' => 'NOURA', '--reason' => 'Verified source document'];
    }

    private function snapshot(): array
    {
        $result = [];
        foreach (['users', 'contracts', 'orders', 'invoices', 'fn_sales', 'fn_bonds', 'fn_constraint_details', 'activity_log'] as $table) {
            $result[$table] = DB::table($table)->orderBy('id')->get()->toArray();
        }
        return $result;
    }

    public function test_dry_run_changes_nothing_and_creates_no_backup(): void
    {
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions())->assertExitCode(0);
        $this->assertEquals($before, $this->snapshot());
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_apply_restores_only_selected_contract_and_is_idempotent(): void
    {
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(0);
        $this->assertDatabaseHas('contracts', ['id' => 514, 'user_id' => 1140]);
        $this->assertDatabaseHas('orders', ['id' => 494, 'user_id' => 1140]);
        $this->assertDatabaseHas('fn_sales', ['id' => 451, 'client_id' => 754, 'debit_client_id' => 6849]);
        $this->assertDatabaseHas('fn_bonds', ['id' => 510, 'credit_account_id' => 6849, 'debit_account_id' => 5464, 'recruitment_contract_id' => 514, 'value' => 100]);
        $this->assertDatabaseHas('fn_constraint_details', ['id' => 510, 'account_id' => 6849, 'creditor_value' => 100]);
        foreach ([578, 5140] as $id) $this->assertDatabaseHas('contracts', ['id' => $id, 'user_id' => 564]);
        foreach ([550, 551] as $id) $this->assertDatabaseHas('fn_bonds', ['id' => $id, 'credit_account_id' => 6339, 'recruitment_contract_id' => null]);
        $this->assertDatabaseHas('fn_sales', ['id' => 490, 'client_id' => 447, 'debit_client_id' => 6339]);
        $this->assertEquals($before['users'], $this->snapshot()['users']);
        $files = Storage::disk('local')->allFiles('data-repairs');
        $this->assertCount(1, $files);
        $backup = json_decode(Storage::disk('local')->get($files[0]), true);
        $this->assertSame(564, $backup['contract']['user_id']);
        $this->assertSame(6339, $backup['bonds'][0]['credit_account_id']);
        $this->assertDatabaseHas('activity_log', ['subject_id' => 514, 'event' => 'client_restored']);
        $after = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(0);
        $this->assertEquals($after, $this->snapshot());
        $this->assertCount(1, Storage::disk('local')->allFiles('data-repairs'));
    }

    /** @dataProvider mismatchedOptions */
    public function test_mismatches_refuse_all_changes(array $options): void
    {
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions($options + ['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function mismatchedOptions(): array
    {
        return [[['--database' => 'other_company']], [['--identity' => 'WRONG']], [['--from-client' => 999]], [['--reason' => '']]];
    }

    public function test_zatca_document_prevents_rewriting_finance_ownership(): void
    {
        DB::table('fn_sales')->where('id', 451)->update(['zatca_invoice_id' => 123]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_shared_order_is_refused(): void
    {
        DB::table('contracts')->where('id', 578)->update(['order_id' => 494]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_failure_after_updates_rolls_back_every_row(): void
    {
        DB::statement("CREATE TRIGGER reject_repair_audit BEFORE INSERT ON activity_log BEGIN SELECT RAISE(ABORT, 'Simulated audit failure'); END");
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
        $this->assertCount(1, Storage::disk('local')->allFiles('data-repairs'));
    }

    public function test_bond_with_conflicting_contract_reference_is_refused(): void
    {
        DB::table('fn_bonds')->where('id', 510)->update(['recruitment_contract_id' => 578]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_refund_prevents_partial_financial_repair(): void
    {
        DB::table('recruitment_contract_refunds')->insert(['id' => 1, 'contract_id' => 514]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_shared_sale_with_a_general_invoice_is_refused(): void
    {
        DB::table('invoices')->insert(['id' => 999, 'contract_id' => null, 'finance_sale_id' => 451]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_shared_invoice_journal_is_refused(): void
    {
        DB::table('invoices')->whereIn('id', [456, 504])->update(['invoice_constraint_id' => 99]);
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }

    public function test_backup_failure_prevents_database_updates(): void
    {
        Storage::shouldReceive('disk')->with('local')->once()->andReturn(new class {
            public function put($path, $contents): bool { return false; }
        });
        $before = $this->snapshot();
        $this->artisan('recruitment-contracts:restore-client', $this->commandOptions(['--apply' => true]))->assertExitCode(1);
        $this->assertEquals($before, $this->snapshot());
    }
}
