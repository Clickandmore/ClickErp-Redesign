<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeVacationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee_vacations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('vacation_id');
            $table->integer('value');
            $table->date('from_date');
            $table->date('to_date');
            $table->integer('days');
            $table->string('reason');
            $table->integer('status')->default(0);
            $table->foreign('employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
            $table->foreign('vacation_id')->references('id')->on('hr_vacations')->onDelete('cascade');
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
        Schema::dropIfExists('hr_employee_vacations');
    }
}
