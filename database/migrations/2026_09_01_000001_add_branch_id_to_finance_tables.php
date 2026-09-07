<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * تعميم الفروع على منظومة المالية والعمولات.
 *
 * `branch_id` يبقى nullable: الشركات التي لا تستخدم الفروع لا تتأثر،
 * والسجلات القديمة تُعالج في migration الـ backfill المنفصل.
 *
 * الملف هنا وليس داخل packages/finance لأن ميزة الفروع مملوكة للتطبيق
 * ويجب أن تعمل حتى لو كانت حزمة المالية submodule غير محدَّث.
 */
class AddBranchIdToFinanceTables extends Migration
{
    /** مستندات المالية التي يجب أن تحمل فرعاً. */
    private array $tables = [
        // شجرة الحسابات ومراكز التكلفة — أساس "شجرة حسابات لكل فرع"
        'fn_accounts',
        'fn_cost_centers',

        // القيود
        'fn_constraints',
        'fn_constraint_details',
        'fn_constraint_files',
        'fn_constraint_logs',

        // السندات
        'fn_bonds',
        'fn_bond_checks',
        'fn_bonds_taxs',

        // الصناديق والتحويلات
        'fn_boxes',
        'fn_account_transfers',

        // العملاء والموردون
        'fn_clients',
        'fn_client_attachments',
        'fn_suppliers',

        // المبيعات والمشتريات
        'fn_sales',
        'fn_sale_items',
        'fn_sales_orders',
        'fn_purchases',
        'fn_purchase_items',
        'fn_purchase_orders',

        // الأصول والمخزون
        'fn_principles',
        'fn_principle_sales',
        'fn_depreciation_principles',
        'fn_inventories',
        'fn_stocks',
        'fn_stock_movements',

        // إقفال السنة المالية
        'fn_fiscal_year_closures',

        // العمولات
        'commission_batches',
        'commission_batch_items',
        'commission_payments',
        'commission_balance_transactions',
        'commission_marketer_overrides',
    ];

    public function up(): void
    {
        foreach ($this->tables as $table) {
            $this->addBranchColumn($table);
        }
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
            $table->unsignedBigInteger('branch_id')->nullable()->after('id');
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

    /** أسماء الفهارس مختصرة يدوياً لأن حد MySQL هو 64 حرفاً. */
    private function indexName(string $tableName): string
    {
        return substr($tableName . '_branch_id_index', 0, 64);
    }

    private function foreignName(string $tableName): string
    {
        return substr($tableName . '_branch_id_foreign', 0, 64);
    }
}
