<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * ربط كل فرع بجذوره المالية.
 *
 * `cost_center_id` مركز تكلفة الفرع الذي تُوسم به قيوده في التقارير المحاسبية،
 * و`branch_finance_accounts` تربط الفرع بحساباته الخاصة في شجرة الحسابات
 * (صندوق، بنك، عملاء، موردون، إيرادات، مصروفات) بحيث لا تختلط أرصدة الفروع.
 *
 * كل شيء nullable: الفرع يعمل تشغيلياً قبل تجهيز شجرته المالية.
 */
class AddFinanceAnchorsToBranchesTable extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('branches')) {
            Schema::table('branches', function (Blueprint $table) {
                if (!Schema::hasColumn('branches', 'cost_center_id')) {
                    $table->unsignedBigInteger('cost_center_id')->nullable();
                }

                if (!Schema::hasColumn('branches', 'finance_provisioned_at')) {
                    $table->timestamp('finance_provisioned_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('branch_finance_accounts')) {
            return;
        }

        Schema::create('branch_finance_accounts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id');
            // cash | bank | clients | suppliers | revenue | expenses
            $table->string('purpose', 40);
            $table->unsignedBigInteger('account_id');
            $table->timestamps();

            $table->unique(['branch_id', 'purpose'], 'branch_finance_accounts_unique');
            $table->index('account_id');

            if (Schema::hasTable('branches')) {
                $table->foreign('branch_id')->references('id')->on('branches')->cascadeOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('branch_finance_accounts');

        if (!Schema::hasTable('branches')) {
            return;
        }

        Schema::table('branches', function (Blueprint $table) {
            foreach (['finance_provisioned_at', 'cost_center_id'] as $column) {
                if (Schema::hasColumn('branches', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
}
