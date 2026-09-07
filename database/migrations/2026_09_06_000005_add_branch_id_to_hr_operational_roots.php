<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hr_payrolls') && ! Schema::hasColumn('hr_payrolls', 'branch_id')) {
            Schema::table('hr_payrolls', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index('branch_id');
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();

                // الشهر الواحد مسموح مرة لكل فرع، لا مرة واحدة للشركة كلها.
                $table->dropUnique('hr_payrolls_month_year_unique');
                $table->unique(['branch_id', 'month_year'], 'hr_payrolls_branch_month_unique');
            });
        }

        if (Schema::hasTable('hr_tasks') && ! Schema::hasColumn('hr_tasks', 'branch_id')) {
            Schema::table('hr_tasks', function (Blueprint $table) {
                $table->unsignedBigInteger('branch_id')->nullable()->after('id');
                $table->index('branch_id');
                $table->foreign('branch_id')->references('id')->on('branches')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('hr_tasks') && Schema::hasColumn('hr_tasks', 'branch_id')) {
            Schema::table('hr_tasks', function (Blueprint $table) {
                $table->dropForeign(['branch_id']);
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }

        if (Schema::hasTable('hr_payrolls') && Schema::hasColumn('hr_payrolls', 'branch_id')) {
            Schema::table('hr_payrolls', function (Blueprint $table) {
                $table->dropUnique('hr_payrolls_branch_month_unique');
                $table->unique('month_year');
                $table->dropForeign(['branch_id']);
                $table->dropIndex(['branch_id']);
                $table->dropColumn('branch_id');
            });
        }
    }
};
