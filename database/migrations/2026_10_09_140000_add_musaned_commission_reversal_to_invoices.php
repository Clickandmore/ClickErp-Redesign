<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * قيد عكس عمولة مساند المسجلة عند إصدار الفاتورة («التسلسل الجديد»)، حين يُسترد العقد
 * قبل أي تحويل من مساند فلا تقع العمولة أصلاً. العمود يمنع العكس مرتين.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'musaned_commission_reversal_constraint_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('musaned_commission_reversal_constraint_id')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'musaned_commission_reversal_constraint_id')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('musaned_commission_reversal_constraint_id');
            });
        }
    }
};
