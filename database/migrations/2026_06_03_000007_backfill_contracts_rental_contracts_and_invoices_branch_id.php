<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillContractsRentalContractsAndInvoicesBranchId extends Migration
{
    public function up()
    {
        $this->backfillContracts();
        $this->backfillRentalContracts();
        $this->backfillInvoices();
    }

    public function down()
    {
        //
    }

    private function backfillContracts(): void
    {
        if (
            !Schema::hasTable('contracts') ||
            !Schema::hasColumn('contracts', 'branch_id')
        ) {
            return;
        }

        if (
            Schema::hasTable('orders') &&
            Schema::hasColumn('contracts', 'order_id') &&
            Schema::hasColumn('orders', 'branch_id')
        ) {
            DB::statement("
                UPDATE contracts
                INNER JOIN orders ON orders.id = contracts.order_id
                SET contracts.branch_id = orders.branch_id
                WHERE contracts.branch_id IS NULL
                  AND orders.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('contracts', 'marketer_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE contracts
                INNER JOIN admins ON admins.id = contracts.marketer_id
                SET contracts.branch_id = admins.branch_id
                WHERE contracts.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillRentalContracts(): void
    {
        if (
            !Schema::hasTable('rental_contracts') ||
            !Schema::hasColumn('rental_contracts', 'branch_id')
        ) {
            return;
        }

        if (
            Schema::hasTable('orders') &&
            Schema::hasColumn('rental_contracts', 'order_id') &&
            Schema::hasColumn('orders', 'branch_id')
        ) {
            DB::statement("
                UPDATE rental_contracts
                INNER JOIN orders ON orders.id = rental_contracts.order_id
                SET rental_contracts.branch_id = orders.branch_id
                WHERE rental_contracts.branch_id IS NULL
                  AND orders.branch_id IS NOT NULL
            ");
        }

        if (
            Schema::hasTable('admins') &&
            Schema::hasColumn('rental_contracts', 'marketer_id') &&
            Schema::hasColumn('admins', 'branch_id')
        ) {
            DB::statement("
                UPDATE rental_contracts
                INNER JOIN admins ON admins.id = rental_contracts.marketer_id
                SET rental_contracts.branch_id = admins.branch_id
                WHERE rental_contracts.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    private function backfillInvoices(): void
    {
        if (
            !Schema::hasTable('invoices') ||
            !Schema::hasTable('contracts') ||
            !Schema::hasColumn('invoices', 'branch_id') ||
            !Schema::hasColumn('invoices', 'contract_id') ||
            !Schema::hasColumn('contracts', 'branch_id')
        ) {
            return;
        }

        DB::statement("
            UPDATE invoices
            INNER JOIN contracts ON contracts.id = invoices.contract_id
            SET invoices.branch_id = contracts.branch_id
            WHERE invoices.branch_id IS NULL
              AND contracts.branch_id IS NOT NULL
        ");
    }
}
