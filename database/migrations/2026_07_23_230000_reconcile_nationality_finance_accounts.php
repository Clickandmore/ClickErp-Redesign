<?php

use App\Services\ingaz\NationalityFinanceAccountReconciliationService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    public function up(): void
    {
        $result = app(NationalityFinanceAccountReconciliationService::class)->reconcile(false);

        if ($result['skipped'] ?? false) {
            Log::warning('Skipped nationality finance account reconciliation migration because required parent accounts were not found.', [
                'missing_parents' => $result['missing_parents'] ?? [],
            ]);
        }
    }

    public function down(): void
    {
        // Account merges preserve all journal rows in the selected canonical
        // accounts, so recreating empty duplicates on rollback is not useful.
    }
};
