<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hr_employee', function (Blueprint $table) {
            if (!Schema::hasColumn('hr_employee', 'employee_category')) {
                $table->string('employee_category', 30)
                    ->default('employee')
                    ->after('status')
                    ->comment('تصنيف السجل داخل الموارد البشرية: موظف أو عاملة');
            }

            if (!Schema::hasColumn('hr_employee', 'maid_id')) {
                $table->unsignedBigInteger('maid_id')
                    ->nullable()
                    ->after('admin_id')
                    ->comment('الربط بسجل العاملة الأصلي');
                $table->foreign('maid_id')
                    ->references('id')
                    ->on('maids')
                    ->nullOnDelete();
                $table->unique('maid_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('hr_employee', function (Blueprint $table) {
            if (Schema::hasColumn('hr_employee', 'maid_id')) {
                $table->dropUnique(['maid_id']);
                $table->dropForeign(['maid_id']);
                $table->dropColumn('maid_id');
            }

            if (Schema::hasColumn('hr_employee', 'employee_category')) {
                $table->dropColumn('employee_category');
            }
        });
    }
};
