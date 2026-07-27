<?php

namespace Tests\Unit;

use App\Services\ingaz\NationalityFinanceAccountReconciliationService;
use Mockery;
use Tests\TestCase;

class NationalityFinanceAccountReconciliationMigrationTest extends TestCase
{
    public function test_migration_uses_non_strict_reconciliation_for_custom_account_trees(): void
    {
        $service = Mockery::mock(NationalityFinanceAccountReconciliationService::class);
        $service->shouldReceive('reconcile')
            ->once()
            ->with(false)
            ->andReturn([
                'merged_accounts' => 0,
                'moved_accounts' => 0,
                'renamed_accounts' => 0,
                'removed_empty_accounts' => 0,
                'skipped' => true,
                'missing_parents' => ['sales', 'cost', 'discount'],
            ]);

        $this->app->instance(NationalityFinanceAccountReconciliationService::class, $service);

        $migration = require database_path(
            'migrations/2026_07_23_230000_reconcile_nationality_finance_accounts.php'
        );

        $migration->up();
    }
}
