<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * أعمدة دورة تكلفة الوكيل الخارجي المربوطة بإعدادات «قيود المشتريات والوكلاء الخارجيون».
 *
 * كلها nullable وبلا قيمة افتراضية سلوكية: العقود والتحويلات القائمة تبقى NULL،
 * وقراءتها تعود إلى السلوك الحالي (on_arrival / intermediate_two_step).
 *
 * حقول ربط القيود على العقد لازمة لوضع on_contract، لأن القيد يُنشأ قبل وجود فاتورة،
 * بينما الحقول المكافئة في جدول invoices تبقى كما هي للأوضاع الأخرى.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                if (!Schema::hasColumn('contracts', 'supplier_cost_recognition_mode')) {
                    $table->string('supplier_cost_recognition_mode', 32)->nullable();
                }
                if (!Schema::hasColumn('contracts', 'external_office_cost_constraint_id')) {
                    $table->unsignedBigInteger('external_office_cost_constraint_id')->nullable();
                }
                if (!Schema::hasColumn('contracts', 'external_office_cost_reversal_constraint_id')) {
                    $table->unsignedBigInteger('external_office_cost_reversal_constraint_id')->nullable();
                }
                if (!Schema::hasColumn('contracts', 'external_office_purchase_id')) {
                    $table->unsignedBigInteger('external_office_purchase_id')->nullable();
                }
                if (!Schema::hasColumn('contracts', 'supplier_cost_skip_reason')) {
                    $table->string('supplier_cost_skip_reason', 64)->nullable();
                }
            });
        }

        if (Schema::hasTable('musaned_transfers') && !Schema::hasColumn('musaned_transfers', 'collection_mode')) {
            Schema::table('musaned_transfers', function (Blueprint $table) {
                $table->string('collection_mode', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('contracts')) {
            Schema::table('contracts', function (Blueprint $table) {
                foreach ([
                    'supplier_cost_recognition_mode',
                    'external_office_cost_constraint_id',
                    'external_office_cost_reversal_constraint_id',
                    'external_office_purchase_id',
                    'supplier_cost_skip_reason',
                ] as $column) {
                    if (Schema::hasColumn('contracts', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('musaned_transfers') && Schema::hasColumn('musaned_transfers', 'collection_mode')) {
            Schema::table('musaned_transfers', function (Blueprint $table) {
                $table->dropColumn('collection_mode');
            });
        }
    }
};
