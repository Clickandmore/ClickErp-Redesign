<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * هذا الملف للتأكد من وجود جميع الحقول المطلوبة في جدول deportation_procedures
     */
    public function up(): void
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // الحقول الأساسية - التحقق من وجودها أولاً
            
            // حقول التواريخ
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
            
            // حقل جدولة الرحلة
            if (!Schema::hasColumn('deportation_procedures', 'trip_scheduled')) {
                $table->boolean('trip_scheduled')->default(false)->after('trip_id')
                    ->comment('هل تم جدولة رحلة السفر');
            }
            
            // حقول التأكيد - إذا لم تكن موجودة
            if (!Schema::hasColumn('deportation_procedures', 'procedures_confirmed')) {
                $table->boolean('procedures_confirmed')->default(false)
                    ->comment('هل تم تأكيد الإجراءات');
            }
            
            if (!Schema::hasColumn('deportation_procedures', 'confirmed_at')) {
                $table->timestamp('confirmed_at')->nullable()
                    ->comment('تاريخ التأكيد');
            }
            
            if (!Schema::hasColumn('deportation_procedures', 'confirmed_by')) {
                $table->foreignId('confirmed_by')->nullable()
                    ->constrained('admins')->onDelete('set null')
                    ->comment('من قام بالتأكيد');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('deportation_procedures', function (Blueprint $table) {
            // حذف الحقول إذا كانت موجودة
            $columns = [
                'passport_date',
                'deportation_letter_date',
                'settlement_date',
                'final_exit_date',
                'trip_scheduled',
                'procedures_confirmed',
                'confirmed_at'
            ];
            
            foreach ($columns as $column) {
                if (Schema::hasColumn('deportation_procedures', $column)) {
                    // حذف foreign key إذا كان العمود confirmed_by
                    if ($column === 'confirmed_by') {
                        $table->dropForeign(['confirmed_by']);
                    }
                    $table->dropColumn($column);
                }
            }
            
            // حذف confirmed_by منفصل لأنه foreign key
            if (Schema::hasColumn('deportation_procedures', 'confirmed_by')) {
                $table->dropForeign(['confirmed_by']);
                $table->dropColumn('confirmed_by');
            }
        });
    }
};