<?php

namespace Tests\Unit;

use App\Services\RuknAdeemRefundVatRepair;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuknAdeemRefundVatRepairTest extends TestCase
{
    public function createApplication()
    {
        $app = require __DIR__.'/../../bootstrap/app.php';
        $app->afterBootstrapping(\Illuminate\Foundation\Bootstrap\LoadConfiguration::class, function ($app) {
            $app['config']->set('database.default', 'repair_test');
            $app['config']->set('database.connections.repair_test', ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']);
        });
        $app->make(\Illuminate\Contracts\Console\Kernel::class)->bootstrap();
        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();
        foreach ([
            'CREATE TABLE admins (id INTEGER PRIMARY KEY)',
            'CREATE TABLE fn_accounts (id INTEGER PRIMARY KEY, code INTEGER, status TEXT, parent_id INTEGER)',
            'CREATE TABLE fn_constraints (id INTEGER PRIMARY KEY, code INTEGER, date TEXT, approval_status TEXT, deleted_at TEXT)',
            'CREATE TABLE fn_constraint_details (id INTEGER PRIMARY KEY, constraint_id INTEGER, account_id INTEGER, date TEXT, type TEXT, debtor_value REAL, creditor_value REAL, cost_center_id INTEGER, statement TEXT, created_at TEXT, updated_at TEXT, deleted_at TEXT)',
            'CREATE TABLE fn_constraint_logs (id INTEGER PRIMARY KEY AUTOINCREMENT, constraint_id INTEGER, admin_id INTEGER, action TEXT, description TEXT, metadata TEXT, created_at TEXT, updated_at TEXT)',
        ] as $sql) { DB::statement($sql); }
        DB::table('admins')->insert(['id' => 7]);
        DB::table('fn_accounts')->insert([
            ['id' => 5462, 'code' => 15600, 'status' => 'debtor'],
            ['id' => 5463, 'code' => 22200, 'status' => 'creditor'],
        ]);
        foreach (RuknAdeemRefundVatRepair::CASES as [$id, $journal, $code, $date, $cents, $center, $original]) {
            foreach ([$journal, $original] as $journalId) {
                DB::table('fn_constraints')->insert(['id' => $journalId, 'code' => $code, 'date' => $date, 'approval_status' => 'approved']);
            }
            $base = ['constraint_id' => $journal, 'date' => $date, 'type' => 'debtor',
                'debtor_value' => $cents / 100, 'creditor_value' => 0, 'cost_center_id' => $center,
                'statement' => 'Original statement', 'created_at' => $date, 'updated_at' => $date];
            DB::table('fn_constraint_details')->insert(array_merge($base, ['id' => $id, 'account_id' => 5462]));
            DB::table('fn_constraint_details')->insert(array_merge($base, ['id' => $id + 100000, 'account_id' => 99, 'type' => 'creditor', 'debtor_value' => 0, 'creditor_value' => $cents / 100]));
            DB::table('fn_constraint_details')->insert(array_merge($base, ['id' => $id + 200000, 'constraint_id' => $original, 'account_id' => 5463, 'type' => 'creditor', 'debtor_value' => 0, 'creditor_value' => $cents / 100]));
        }
    }

    private function repair(): RuknAdeemRefundVatRepair
    {
        // Only tests bypass the actual database-name check; production has no bypass option.
        return new class extends RuknAdeemRefundVatRepair {
            protected function actualDatabase(): string { return self::DATABASE; }
        };
    }

    private function snapshot(): array
    {
        return DB::table('fn_constraint_details')->orderBy('id')->get()->map(fn ($row) => (array) $row)->all();
    }

    public function test_preview_does_not_write(): void
    {
        $before = $this->snapshot();
        $this->assertSame('preview', $this->repair()->run()['status']);
        $this->assertSame($before, $this->snapshot());
        $this->assertSame(0, DB::table('fn_constraint_logs')->count());
    }

    public function test_apply_changes_only_four_accounts_and_is_idempotent(): void
    {
        $before = $this->snapshot();
        $this->assertSame(4, $this->repair()->run(true, 7)['changed']);
        $expected = array_map(function ($row) {
            if (in_array($row['id'], array_column(RuknAdeemRefundVatRepair::CASES, 0))) { $row['account_id'] = 5463; }
            return $row;
        }, $before);
        $this->assertSame($expected, $this->snapshot());
        $this->assertSame(4, DB::table('fn_constraint_logs')->count());
        $this->assertSame('already_applied', $this->repair()->run(true, 7)['status']);
        $this->assertSame(4, DB::table('fn_constraint_logs')->count());
        $this->assertSame($expected, $this->snapshot());
    }

    public function test_cli_refuses_non_mysql_database(): void
    {
        $before = $this->snapshot();
        $this->artisan('finance:repair-rukn-adeem-refund-vat', ['--apply' => true, '--admin-id' => 7])->assertExitCode(1);
        $this->assertSame($before, $this->snapshot());
    }

    public function test_wrong_database_name_is_refused(): void
    {
        $repair = new class extends RuknAdeemRefundVatRepair {
            protected function actualDatabase(): string { return 'another_company'; }
        };
        $this->expectException(\RuntimeException::class);
        $repair->run(true, 7);
    }

    /** @dataProvider invalidStates */
    public function test_mismatches_leave_all_lines_untouched(string $table, array $where, array $change): void
    {
        DB::table($table)->where($where)->update($change);
        $before = $this->snapshot();
        try {
            $this->repair()->run(true, 7);
            $this->fail('Expected refusal');
        } catch (\RuntimeException $e) {
            $this->assertSame($before, $this->snapshot());
            $this->assertSame(0, DB::table('fn_constraint_logs')->count());
        }
    }

    public static function invalidStates(): array
    {
        return [
            ['fn_constraint_details', ['id' => 17522], ['debtor_value' => 211]],
            ['fn_constraint_details', ['id' => 17522], ['account_id' => 99]],
            ['fn_constraint_details', ['id' => 17522], ['account_id' => 5463]],
            ['fn_constraint_details', ['id' => 17522], ['deleted_at' => '2026-09-07']],
            ['fn_constraint_details', ['id' => 17522], ['cost_center_id' => 9]],
            ['fn_constraints', ['id' => 3967], ['code' => 123]],
            ['fn_constraints', ['id' => 3967], ['approval_status' => 'pending']],
            ['fn_accounts', ['id' => 5463], ['code' => 999]],
            ['fn_constraint_details', ['id' => 217522], ['creditor_value' => 200]],
        ];
    }

    public function test_missing_actor_refused(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->repair()->run(true);
    }

    public function test_audit_failure_rolls_back_updates_and_previous_logs(): void
    {
        DB::statement("CREATE TRIGGER reject_log BEFORE INSERT ON fn_constraint_logs WHEN NEW.constraint_id = 3967 BEGIN SELECT RAISE(ABORT, 'test audit failure'); END");
        $before = $this->snapshot();
        try {
            $this->repair()->run(true, 7);
            $this->fail('Expected audit failure');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertSame($before, $this->snapshot());
            $this->assertSame(0, DB::table('fn_constraint_logs')->count());
        }
    }
}
