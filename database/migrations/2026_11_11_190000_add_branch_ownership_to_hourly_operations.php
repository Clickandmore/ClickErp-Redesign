<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /** @var array<int, string> */
    private array $tables = [
        'hourly_rental_orders',
        'hourly_visits',
        'hourly_order_payments',
        'hourly_order_status_logs',
        'hourly_cash_collections',
        'hourly_cash_remittances',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'branch_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $blueprint) {
                $blueprint->foreignId('branch_id')->nullable()->after('id')
                    ->constrained('branches')->restrictOnDelete();
            });
        }

        $this->backfillOrders();
        $this->inheritFromParent('hourly_visits', 'hourly_rental_order_id', 'hourly_rental_orders');
        $this->inheritFromParent('hourly_order_payments', 'hourly_rental_order_id', 'hourly_rental_orders');
        $this->inheritFromParent('hourly_order_status_logs', 'hourly_rental_order_id', 'hourly_rental_orders');
        $this->inheritFromParent('hourly_cash_collections', 'order_id', 'hourly_rental_orders');
        $this->inheritFromParent('hourly_cash_remittances', 'collection_id', 'hourly_cash_collections');

        $mainBranchId = Schema::hasTable('branches')
            ? DB::table('branches')->where('is_main', true)->value('id') ?: DB::table('branches')->orderBy('id')->value('id')
            : null;

        if ($mainBranchId) {
            foreach ($this->tables as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                    DB::table($table)->whereNull('branch_id')->update(['branch_id' => $mainBranchId]);
                }
            }
        }
    }

    public function down(): void
    {
        foreach (array_reverse($this->tables) as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropConstrainedForeignId('branch_id'));
            }
        }
    }

    private function backfillOrders(): void
    {
        if (! Schema::hasTable('hourly_rental_orders') || ! Schema::hasColumn('hourly_rental_orders', 'branch_id')) {
            return;
        }

        if (Schema::hasTable('admins') && Schema::hasColumn('admins', 'branch_id')) {
            DB::statement('UPDATE hourly_rental_orders orders JOIN admins ON admins.id = orders.created_by_admin_id SET orders.branch_id = admins.branch_id WHERE orders.branch_id IS NULL AND admins.branch_id IS NOT NULL');
        }

        if (Schema::hasTable('users') && Schema::hasColumn('users', 'branch_id')) {
            DB::statement('UPDATE hourly_rental_orders orders JOIN users ON users.id = orders.user_id SET orders.branch_id = users.branch_id WHERE orders.branch_id IS NULL AND users.branch_id IS NOT NULL');
        }
    }

    private function inheritFromParent(string $table, string $foreignKey, string $parentTable): void
    {
        if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'branch_id')
            || ! Schema::hasTable($parentTable) || ! Schema::hasColumn($parentTable, 'branch_id')) {
            return;
        }

        DB::statement("UPDATE {$table} children JOIN {$parentTable} parent ON parent.id = children.{$foreignKey} SET children.branch_id = parent.branch_id WHERE children.branch_id IS NULL AND parent.branch_id IS NOT NULL");
    }
};
