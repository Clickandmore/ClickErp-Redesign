<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillOrdersBranchId extends Migration
{
    public function up()
    {
        if (
            !Schema::hasTable('orders') ||
            !Schema::hasTable('admins') ||
            !Schema::hasColumn('orders', 'branch_id') ||
            !Schema::hasColumn('admins', 'branch_id')
        ) {
            return;
        }

        if (Schema::hasColumn('orders', 'customer_id')) {
            DB::statement("
                UPDATE orders
                INNER JOIN admins ON admins.id = orders.customer_id
                SET orders.branch_id = admins.branch_id
                WHERE orders.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }

        if (Schema::hasTable('order_details') && Schema::hasColumn('order_details', 'admin_id')) {
            DB::statement("
                UPDATE orders
                INNER JOIN order_details ON order_details.order_id = orders.id
                INNER JOIN admins ON admins.id = order_details.admin_id
                SET orders.branch_id = admins.branch_id
                WHERE orders.branch_id IS NULL
                  AND admins.branch_id IS NOT NULL
            ");
        }
    }

    public function down()
    {
        //
    }
}
