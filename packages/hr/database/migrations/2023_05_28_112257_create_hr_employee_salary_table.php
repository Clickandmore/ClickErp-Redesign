<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeSalaryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee_salary', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->string('month_year');
            $table->double('sanctions')->default(0);
            $table->double('allowances')->default(0);
            $table->double('Insurances')->default(0);
            $table->double('tax')->default(0);
            $table->double('delays')->default(0);
            $table->double('attandance_days')->default(0);
            $table->double('final')->default(0);
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
        Schema::dropIfExists('hr_employee_salary');
    }
}
