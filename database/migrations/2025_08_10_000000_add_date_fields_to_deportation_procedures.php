<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // إضافة حقول التواريخ المفقودة - مع التحقق من عدم وجودها
            if (!Schema::hasColumn('deportation_procedures', 'passport_date')) {
                $table->date('passport_date')->nullable()->after('has_passport')
                    ->comment('تاريخ استلام الجواز');
            }

            if (!Schema::hasColumn('deportation_procedures', 'deportation_letter_date')) {
                $table->date('deportation_letter_date')->nullable()->after('has_deportation_letter')
                    ->comment('تاريخ خطاب الترحيل');
            }

            if (!Schema::hasColumn('deportation_procedures', 'settlement_date')) {
                $table->date('settlement_date')->nullable()->after('has_settlement_session')
                    ->comment('تاريخ جلسة التسوية');
            }

            if (!Schema::hasColumn('deportation_procedures', 'final_exit_date')) {
                $table->date('final_exit_date')->nullable()->after('has_final_exit')
                    ->comment('تاريخ الخروج النهائي');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // حذف الأعمدة إذا كانت موجودة
            $columns = [
                'passport_date',
                'deportation_letter_date',
                'settlement_date',
                'final_exit_date'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('deportation_procedures', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
