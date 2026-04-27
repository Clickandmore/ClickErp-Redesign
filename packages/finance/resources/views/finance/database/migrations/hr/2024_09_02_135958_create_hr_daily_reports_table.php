<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrDailyReportsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_daily_work_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('employee_id');
            $table->foreign('employee_id')->references('id')->on('hr_employee')->onDelete('cascade')->name('hr_d_e_id_foreign');
            $table->string('title');
            $table->text('description');
            $table->date('date');
            $table->string('file')->nullable();
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
        Schema::dropIfExists('hr_daily_work_reports');
    }
}
