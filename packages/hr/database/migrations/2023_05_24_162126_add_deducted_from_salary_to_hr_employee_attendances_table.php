<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeductedFromSalaryToHrEmployeeAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            $table->dropColumn('deducted_from_salary');
            $table->dropColumn('deducted_from_salary_vlaue');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            $table->integer('deducted_from_salary')->default(0);
            $table->double('deducted_from_salary_vlaue')->default(0);
        });
    }
}
