<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToContractsRentalContractsAndInvoicesTables extends Migration
{
    public function up()
    {
        $this->addBranchId('contracts', 'order_id');
        $this->addBranchId('rental_contracts', 'order_id');
        $this->addBranchId('invoices', 'contract_id');
    }

    public function down()
    {
        $this->dropBranchId('invoices');
        $this->dropBranchId('rental_contracts');
        $this->dropBranchId('contracts');
    }

    private function addBranchId(string $tableName, ?string $afterColumn = null): void
    {
        if (!Schema::hasTable($tableName) || Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName, $afterColumn) {
            $column = $table->unsignedBigInteger('branch_id')->nullable();

            if ($afterColumn && Schema::hasColumn($tableName, $afterColumn)) {
                $column->after($afterColumn);
            }

            $table->index('branch_id', "{$tableName}_branch_id_index");

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id', "{$tableName}_branch_id_foreign")
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });
    }

    private function dropBranchId(string $tableName): void
    {
        if (!Schema::hasTable($tableName) || !Schema::hasColumn($tableName, 'branch_id')) {
            return;
        }

        Schema::table($tableName, function (Blueprint $table) use ($tableName) {
            if (Schema::hasTable('branches')) {
                try {
                    $table->dropForeign("{$tableName}_branch_id_foreign");
                } catch (\Throwable $e) {
                    //
                }
            }

            try {
                $table->dropIndex("{$tableName}_branch_id_index");
            } catch (\Throwable $e) {
                //
            }

            $table->dropColumn('branch_id');
        });
    }
}
