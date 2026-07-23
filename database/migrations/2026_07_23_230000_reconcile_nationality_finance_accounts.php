<?php

use App\Services\ingaz\NationalityFinanceAccountReconciliationService;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    public function up(): void
    {
        app(NationalityFinanceAccountReconciliationService::class)->reconcile();
    }

    public function down(): void
    {
        // Account merges preserve all journal rows in the selected canonical
        // accounts, so recreating empty duplicates on rollback is not useful.
    }
};
