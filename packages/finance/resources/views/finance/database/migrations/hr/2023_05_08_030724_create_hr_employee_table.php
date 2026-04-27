<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrEmployeeTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_employee', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('image')->default('default.png');
            $table->string('code')->default(' ');
            $table->string('phone');
            $table->date('birthday');
            $table->string('email');
            $table->string('national_num');
            $table->string('address')->default(' ');
            $table->date('hired_date');
            $table->date('contract_end_date');
            $table->double('salary');
            $table->boolean('status')->default(1);
            $table->unsignedBigInteger('deparment_id');
            $table->foreign('deparment_id')->references('id')->on('hr_department')->onDelete('cascade');
            $table->unsignedBigInteger('job_id');
            $table->foreign('job_id')->references('id')->on('hr_jobs')->onDelete('cascade');
            $table->unsignedBigInteger('country_id');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
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
        Schema::dropIfExists('hr_employee');
    }
}
