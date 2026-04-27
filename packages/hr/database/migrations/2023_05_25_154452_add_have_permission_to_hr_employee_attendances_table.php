<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddHavePermissionToHrEmployeeAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hr_employee_attendances', function (Blueprint $table) {
            $table->integer('have_permission')->default(0)->after('employee_id');

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
            $table->dropColumn('have_permission');
        });
    }
}
