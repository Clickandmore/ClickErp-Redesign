<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToOperationalTables extends Migration
{
    private array $tables = [
        'recruitment_requests',
        'complaints',
        'complaint_comments',
        'complaint_timelines',
        'complaint_feedbacks',
        'contract_payments',
        'contract_notes',
        'contract_status_logs',
        'sponsorship_transfers',
        'transfer_service_contracts',
        'musaned_transfers',
        'musaned_transfer_items',
        'ingaz_delegations',
        'rental_financial_adjustments',
        'rental_contract_invoices',
        'rental_contract_extensions',
        'maid_attachments',
        'client_attachments',
        'recruitment_contract_attachments',
        'maid_recruitment_procedures',
        'contract_external_office_dispatches',
        'recruitment_contract_refunds',
        'contract_return_requests',
        'financial_requests',
        'financial_request_attachments',
        'order_transfers',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addBranchColumn($table);
        }

        $this->backfillFromContracts([
            'contract_payments',
            'contract_notes',
            'contract_status_logs',
            'sponsorship_transfers',
            'transfer_service_contracts',
            'recruitment_contract_attachments',
            'maid_recruitment_procedures',
            'contract_external_office_dispatches',
            'recruitment_contract_refunds',
            'contract_return_requests',
        ]);

        $this->backfillFromRentalContracts([
            'rental_financial_adjustments',
            'rental_contract_invoices',
            'rental_contract_extensions',
        ]);

        $this->backfillFinancialRequests();
        $this->backfillFromOrders([
            'order_transfers',
        ]);
        $this->backfillComplaints();
        $this->backfillComplaintChildren();
        $this->backfillRecruitmentRequests();
        $this->backfillIngazDelegations();
        $this->backfillClientAttachments();
        $this->backfillMaidAttachments();
        $this->backfillMusanedTransfers();
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            $this->dropBranchColumn($table);
        }
    }

    private function addBranchColumn(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->index('branch_id', $this->indexName($tableName));

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id', $this->foreignName($tableName))
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });
    }

    private function dropBranchColumn(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasTable('branches')) {
                $table->dropForeign($this->foreignName($tableName));
            }

            $table->dropIndex($this->indexName($tableName));
            $table->dropColumn('branch_id');
        });
    }

    private function backfillFromContracts(array $tableNames): void
    {
        if (!Schema::hasTable('contracts') || !Schema::hasColumn('contracts', 'branch_id')) {
            return;
        }

        foreach ($tableNames as $tableName) {
            if (
                !Schema::hasTable($tableName) ||
                !Schema::hasColumn($tableName, 'branch_id') ||
                !Schema::hasColumn($tableName, 'contract_id')
            ) {
                continue;
            }

            DB::statement("
                UPDATE {$tableName}
                INNER JOIN contracts ON contracts.id = {$tableName}.contract_id
                SET {$tableName}.branch_id = contracts.branch_id
                WHERE {$tableName}.branch_id IS NULL
                  AND contracts.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillFromRentalContracts(array $tableNames): void
    {
        if (!Schema::hasTable('rental_contracts') || !Schema::hasColumn('rental_contracts', 'branch_id')) {
            return;
        }

        foreach ($tableNames as $tableName) {
            if (
                !Schema::hasTable($tableName) ||
                !Schema::hasColumn($tableName, 'branch_id') ||
                !Schema::hasColumn($tableName, 'rental_contract_id')
            ) {
                continue;
            }

            DB::statement("
                UPDATE {$tableName}
                INNER JOIN rental_contracts ON rental_contracts.id = {$tableName}.rental_contract_id
                SET {$tableName}.branch_id = rental_contracts.branch_id
                WHERE {$tableName}.branch_id IS NULL
                  AND rental_contracts.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillFromOrders(array $tableNames): void
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'branch_id')) {
            return;
        }

        foreach ($tableNames as $tableName) {
            if (
                !Schema::hasTable($tableName) ||
                !Schema::hasColumn($tableName, 'branch_id') ||
                !Schema::hasColumn($tableName, 'order_id')
            ) {
                continue;
            }

            DB::statement("
                UPDATE {$tableName}
                INNER JOIN orders ON orders.id = {$tableName}.order_id
                SET {$tableName}.branch_id = orders.branch_id
                WHERE {$tableName}.branch_id IS NULL
                  AND orders.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillFinancialRequests(): void
    {
        if (!Schema::hasTable('financial_requests') || !Schema::hasColumn('financial_requests', 'branch_id')) {
            return;
        }

        if (
            Schema::hasTable('contracts') &&
            Schema::hasColumn('financial_requests', 'contract_id') &&
            Schema::hasColumn('financial_requests', 'contract_type') &&
            Schema::hasColumn('contracts', 'branch_id')
        ) {
            DB::statement("
                UPDATE financial_requests
                INNER JOIN contracts ON contracts.id = financial_requests.contract_id
                SET financial_requests.branch_id = contracts.branch_id
                WHERE financial_requests.branch_id IS NULL
                  AND (financial_requests.contract_type IS NULL OR financial_requests.contract_type <> 'rental')
                  AND contracts.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('rental_contracts') &&
            Schema::hasColumn('financial_requests', 'contract_id') &&
            Schema::hasColumn('financial_requests', 'contract_type') &&
            Schema::hasColumn('rental_contracts', 'branch_id')
        ) {
            DB::statement("
                UPDATE financial_requests
                INNER JOIN rental_contracts ON rental_contracts.id = financial_requests.contract_id
                SET financial_requests.branch_id = rental_contracts.branch_id
                WHERE financial_requests.branch_id IS NULL
                  AND financial_requests.contract_type = 'rental'
                  AND rental_contracts.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('financial_requests', 'client_id') &&
            Schema::hasColumn('users', 'branch_id')
        ) {
            DB::statement("
                UPDATE financial_requests
                INNER JOIN users ON users.id = financial_requests.client_id
                SET financial_requests.branch_id = users.branch_id
                WHERE financial_requests.branch_id IS NULL
                  AND users.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('financial_requests', 'created_by_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE financial_requests
                INNER JOIN admins ON admins.id = financial_requests.created_by_id
                SET financial_requests.branch_id = admins.branch_id
                WHERE financial_requests.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('financial_request_attachments') &&
            Schema::hasColumn('financial_request_attachments', 'branch_id') &&
            Schema::hasColumn('financial_request_attachments', 'financial_request_id')
        ) {
            DB::statement("
                UPDATE financial_request_attachments
                INNER JOIN financial_requests ON financial_requests.id = financial_request_attachments.financial_request_id
                SET financial_request_attachments.branch_id = financial_requests.branch_id
                WHERE financial_request_attachments.branch_id IS NULL
                  AND financial_requests.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillComplaints(): void
    {
        if (
            Schema::hasTable('complaints') &&
            Schema::hasTable('users') &&
            Schema::hasColumn('complaints', 'branch_id') &&
            Schema::hasColumn('complaints', 'user_id') &&
            Schema::hasColumn('users', 'branch_id')
        ) {
            DB::statement("
                UPDATE complaints
                INNER JOIN users ON users.id = complaints.user_id
                SET complaints.branch_id = users.branch_id
                WHERE complaints.branch_id IS NULL
                  AND users.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('complaints') &&
            Schema::hasTable('admins') &&
            Schema::hasColumn('complaints', 'branch_id') &&
            Schema::hasColumn('complaints', 'assigned_to') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE complaints
                INNER JOIN admins ON admins.id = complaints.assigned_to
                SET complaints.branch_id = admins.branch_id
                WHERE complaints.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillComplaintChildren(): void
    {
        if (!Schema::hasTable('complaints') || !Schema::hasColumn('complaints', 'branch_id')) {
            return;
        }

        foreach (['complaint_comments', 'complaint_timelines', 'complaint_feedbacks'] as $tableName) {
            if (
                !Schema::hasTable($tableName) ||
                !Schema::hasColumn($tableName, 'branch_id') ||
                !Schema::hasColumn($tableName, 'complaint_id')
            ) {
                continue;
            }

            DB::statement("
                UPDATE {$tableName}
                INNER JOIN complaints ON complaints.id = {$tableName}.complaint_id
                SET {$tableName}.branch_id = complaints.branch_id
                WHERE {$tableName}.branch_id IS NULL
                  AND complaints.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillRecruitmentRequests(): void
    {
        if (!Schema::hasTable('recruitment_requests') || !Schema::hasColumn('recruitment_requests', 'branch_id')) {
            return;
        }

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('recruitment_requests', 'client_id') &&
            Schema::hasColumn('users', 'branch_id')
        ) {
            DB::statement("
                UPDATE recruitment_requests
                INNER JOIN users ON users.id = recruitment_requests.client_id
                SET recruitment_requests.branch_id = users.branch_id
                WHERE recruitment_requests.branch_id IS NULL
                  AND users.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('recruitment_requests', 'admin_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE recruitment_requests
                INNER JOIN admins ON admins.id = recruitment_requests.admin_id
                SET recruitment_requests.branch_id = admins.branch_id
                WHERE recruitment_requests.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillIngazDelegations(): void
    {
        if (!Schema::hasTable('ingaz_delegations') || !Schema::hasColumn('ingaz_delegations', 'branch_id')) {
            return;
        }

        if (
            Schema::hasTable('users') &&
            Schema::hasColumn('ingaz_delegations', 'user_id') &&
            Schema::hasColumn('users', 'branch_id')
        ) {
            DB::statement("
                UPDATE ingaz_delegations
                INNER JOIN users ON users.id = ingaz_delegations.user_id
                SET ingaz_delegations.branch_id = users.branch_id
                WHERE ingaz_delegations.branch_id IS NULL
                  AND users.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('ingaz_delegations', 'marketer_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE ingaz_delegations
                INNER JOIN admins ON admins.id = ingaz_delegations.marketer_id
                SET ingaz_delegations.branch_id = admins.branch_id
                WHERE ingaz_delegations.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillClientAttachments(): void
    {
        if (
            !Schema::hasTable('client_attachments') ||
            !Schema::hasTable('users') ||
            !Schema::hasColumn('client_attachments', 'branch_id') ||
            !Schema::hasColumn('client_attachments', 'user_id') ||
            !Schema::hasColumn('users', 'branch_id')
        ) {
            return;
        }

        DB::statement("
            UPDATE client_attachments
            INNER JOIN users ON users.id = client_attachments.user_id
            SET client_attachments.branch_id = users.branch_id
            WHERE client_attachments.branch_id IS NULL
              AND users.branch_id IS NOT NULL
        ");
    }

    private function backfillMaidAttachments(): void
    {
        if (!Schema::hasTable('maid_attachments') || !Schema::hasColumn('maid_attachments', 'branch_id')) {
            return;
        }

        if (
            Schema::hasTable('contracts') &&
            Schema::hasColumn('maid_attachments', 'maid_id') &&
            Schema::hasColumn('contracts', 'branch_id') &&
            Schema::hasColumn('contracts', 'maid_id')
        ) {
            DB::statement("
                UPDATE maid_attachments
                INNER JOIN (
                    SELECT maid_id, MAX(branch_id) AS branch_id
                    FROM contracts
                    WHERE branch_id IS NOT NULL
                    GROUP BY maid_id
                ) AS contract_branches ON contract_branches.maid_id = maid_attachments.maid_id
                SET maid_attachments.branch_id = contract_branches.branch_id
                WHERE maid_attachments.branch_id IS NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('maid_attachments', 'uploaded_by') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE maid_attachments
                INNER JOIN admins ON admins.id = maid_attachments.uploaded_by
                SET maid_attachments.branch_id = admins.branch_id
                WHERE maid_attachments.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillMusanedTransfers(): void
    {
        if (
            Schema::hasTable('musaned_transfer_items') &&
            Schema::hasColumn('musaned_transfer_items', 'branch_id')
        ) {
            if (
                Schema::hasTable('contracts') &&
                Schema::hasColumn('musaned_transfer_items', 'contract_id') &&
                Schema::hasColumn('contracts', 'branch_id')
            ) {
                DB::statement("
                    UPDATE musaned_transfer_items
                    INNER JOIN contracts ON contracts.id = musaned_transfer_items.contract_id
                    SET musaned_transfer_items.branch_id = contracts.branch_id
                    WHERE musaned_transfer_items.branch_id IS NULL
                      AND contracts.branch_id IS NOT NULL
                ");
            }

            if (
                Schema::hasTable('invoices') &&
                Schema::hasColumn('musaned_transfer_items', 'invoice_id') &&
                Schema::hasColumn('invoices', 'branch_id')
            ) {
                DB::statement("
                    UPDATE musaned_transfer_items
                    INNER JOIN invoices ON invoices.id = musaned_transfer_items.invoice_id
                    SET musaned_transfer_items.branch_id = invoices.branch_id
                    WHERE musaned_transfer_items.branch_id IS NULL
                      AND invoices.branch_id IS NOT NULL
                ");
            }
        }

        if (
            Schema::hasTable('musaned_transfers') &&
            Schema::hasTable('musaned_transfer_items') &&
            Schema::hasColumn('musaned_transfers', 'branch_id') &&
            Schema::hasColumn('musaned_transfer_items', 'branch_id')
        ) {
            DB::statement("
                UPDATE musaned_transfers
                INNER JOIN (
                    SELECT musaned_transfer_id, MIN(branch_id) AS branch_id, COUNT(DISTINCT branch_id) AS branches_count
                    FROM musaned_transfer_items
                    WHERE branch_id IS NOT NULL
                    GROUP BY musaned_transfer_id
                ) AS transfer_branches ON transfer_branches.musaned_transfer_id = musaned_transfers.id
                SET musaned_transfers.branch_id = transfer_branches.branch_id
                WHERE musaned_transfers.branch_id IS NULL
                  AND transfer_branches.branches_count = 1
            ");
        }

        if (
            Schema::hasTable('musaned_transfers') &&
            Schema::hasTable('admins') &&
            Schema::hasColumn('musaned_transfers', 'branch_id') &&
            Schema::hasColumn('musaned_transfers', 'created_by_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE musaned_transfers
                INNER JOIN admins ON admins.id = musaned_transfers.created_by_id
                SET musaned_transfers.branch_id = admins.branch_id
                WHERE musaned_transfers.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function indexName(string $tableName): string
    {
        return substr($tableName . '_branch_id_idx', 0, 64);
    }

    private function foreignName(string $tableName): string
    {
        return substr($tableName . '_branch_id_fk', 0, 64);
    }
}
