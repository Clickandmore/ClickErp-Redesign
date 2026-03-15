<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('orders') ||
            !Schema::hasTable('order_details') ||
            !Schema::hasTable('admins') ||
            !Schema::hasColumn('orders', 'customer_id') ||
            !Schema::hasColumn('order_details', 'admin_id')
        ) {
            return;
        }

        DB::statement("
            UPDATE orders
            INNER JOIN order_details ON order_details.order_id = orders.id
            INNER JOIN admins ON admins.id = order_details.admin_id
            SET orders.customer_id = order_details.admin_id,
                orders.updated_at = NOW()
            WHERE orders.customer_id IS NULL
              AND order_details.admin_id IS NOT NULL
              AND admins.admin_type = 'customer_service'
        ");

        DB::statement("
            UPDATE order_details
            INNER JOIN orders ON orders.id = order_details.order_id
            INNER JOIN admins ON admins.id = orders.customer_id
            SET order_details.admin_id = orders.customer_id,
                order_details.updated_at = NOW()
            WHERE order_details.admin_id IS NULL
              AND orders.customer_id IS NOT NULL
              AND admins.admin_type = 'customer_service'
        ");

        DB::statement("
            INSERT INTO order_details (order_id, admin_id, created_at, updated_at)
            SELECT orders.id, orders.customer_id, NOW(), NOW()
            FROM orders
            LEFT JOIN order_details ON order_details.order_id = orders.id
            INNER JOIN admins ON admins.id = orders.customer_id
            WHERE order_details.id IS NULL
              AND orders.customer_id IS NOT NULL
              AND admins.admin_type = 'customer_service'
        ");
    }

    public function down(): void
    {
        // Data sync migration; no rollback.
    }
};
