<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * يثبّت على الفاتورة مكان قيد التكاليف البنكية لمساند (قيد مستقل أو داخل قيد سند القبض)،
 * فلا يتغير تعديلها أو استردادها بتغيير الإعداد. القيمة الفارغة تعني القيد المستقل.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('invoices') && !Schema::hasColumn('invoices', 'musaned_commission_posting')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->string('musaned_commission_posting', 32)->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('invoices') && Schema::hasColumn('invoices', 'musaned_commission_posting')) {
            Schema::table('invoices', function (Blueprint $table) {
                $table->dropColumn('musaned_commission_posting');
            });
        }
    }
};
