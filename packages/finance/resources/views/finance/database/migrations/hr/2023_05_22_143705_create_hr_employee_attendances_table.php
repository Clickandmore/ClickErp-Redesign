<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeAttendancesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee_attendances', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->integer('type');
            $table->time('attendance_time')->default('00:00');
            $table->time('leave_time')->default('00:00');
            $table->string('month_year');
            $table->integer('deducted_from_salary')->default(0);
            $table->double('deducted_from_salary_vlaue')->default(0);
            $table->integer('shift_id');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('hr_employee_attendances');
    }
}
