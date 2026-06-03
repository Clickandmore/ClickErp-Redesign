<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBranchIdToOrdersTable extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('orders') || Schema::hasColumn('orders', 'branch_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            $column = $table->unsignedBigInteger('branch_id')->nullable();

            if (Schema::hasColumn('orders', 'customer_id')) {
                $column->after('customer_id');
            }

            $table->index('branch_id', 'orders_branch_id_index');

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id', 'orders_branch_id_foreign')
                    ->references('id')
                    ->on('branches')
                    ->nullOnDelete();
            }
        });
    }

    public function down()
    {
        if (!Schema::hasTable('orders') || !Schema::hasColumn('orders', 'branch_id')) {
            return;
        }

        Schema::table('orders', function (Blueprint $table) {
            if (Schema::hasTable('branches')) {
                try {
                    $table->dropForeign('orders_branch_id_foreign');
                } catch (\Throwable $e) {
                    //
                }
            }

            try {
                $table->dropIndex('orders_branch_id_index');
            } catch (\Throwable $e) {
                //
            }

            $table->dropColumn('branch_id');
        });
    }
}
