<?php

namespace App\Console\Commands;

use App\Services\ingaz\NationalityFinanceAccountReconciliationService;
use Illuminate\Console\Command;

class ReconcileNationalityFinanceAccountsCommand extends Command
{
    protected $signature = 'finance:reconcile-nationality-accounts';

    protected $description = 'Merge duplicate nationality finance accounts and restore the canonical chart paths';

    public function handle(NationalityFinanceAccountReconciliationService $service): int
    {
        $result = $service->reconcile();

        $this->info('Nationality finance accounts reconciled.');
        $this->table(
            ['Merged', 'Moved', 'Renamed', 'Removed empty'],
            [[
                $result['merged_accounts'],
                $result['moved_accounts'],
                $result['renamed_accounts'],
                $result['removed_empty_accounts'],
            ]]
        );

        return self::SUCCESS;
    }
}
