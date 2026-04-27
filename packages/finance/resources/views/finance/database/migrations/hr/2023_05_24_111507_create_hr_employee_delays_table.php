<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeDelaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee_delays', function (Blueprint $table) {
            $table->id();
            $table->string('month_year');
            $table->double('value');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
            $table->unsignedBigInteger('employee_attendances_id');
            $table->foreign('employee_attendances_id')->references('id')->on('hr_employee_attendances')->onDelete('cascade');
            $table->unsignedBigInteger('sanction_id');
            $table->foreign('sanction_id')->references('id')->on('hr_sanctions')->onDelete('cascade');
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
        Schema::dropIfExists('hr_employee_delays');
    }
}
