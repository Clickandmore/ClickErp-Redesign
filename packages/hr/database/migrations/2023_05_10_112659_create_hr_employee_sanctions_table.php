<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeSanctionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee_sanctions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('sanction_id');
            $table->integer('value');
            $table->date('date');
            $table->boolean('with_salary')->default(0);
            $table->string('reason');
            $table->string('status')->default(0);
            $table->foreign('employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
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
        Schema::dropIfExists('hr_employee_sanctions');
    }
}
