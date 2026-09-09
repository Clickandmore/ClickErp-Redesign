<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        $hasMode = Schema::hasColumn('invoices', 'revenue_recognition_mode');
        $hasReversal = Schema::hasColumn('invoices', 'external_office_cost_reversal_constraint_id');

        if ($hasMode && $hasReversal) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($hasMode, $hasReversal) {
            if (!$hasMode) {
                // null = فاتورة قديمة أُنشئت قبل الإعداد، وتُعامل كالتزام عند الوصول.
                $table->string('revenue_recognition_mode', 20)
                    ->nullable()
                    ->after('external_office_cost_constraint_id');
            }

            if (!$hasReversal) {
                $table->unsignedBigInteger('external_office_cost_reversal_constraint_id')
                    ->nullable()
                    ->after('revenue_recognition_mode');
                $table->foreign('external_office_cost_reversal_constraint_id', 'invoices_ext_office_cost_reversal_fk')
                    ->references('id')
                    ->on('fn_constraints')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('invoices')) {
            return;
        }

        $hasMode = Schema::hasColumn('invoices', 'revenue_recognition_mode');
        $hasReversal = Schema::hasColumn('invoices', 'external_office_cost_reversal_constraint_id');

        if (!$hasMode && !$hasReversal) {
            return;
        }

        Schema::table('invoices', function (Blueprint $table) use ($hasMode, $hasReversal) {
            if ($hasReversal) {
                $table->dropForeign('invoices_ext_office_cost_reversal_fk');
                $table->dropColumn('external_office_cost_reversal_constraint_id');
            }

            if ($hasMode) {
                $table->dropColumn('revenue_recognition_mode');
            }
        });
    }
};
