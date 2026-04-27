<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrTaskRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_task_replies', function (Blueprint $table) {
            $table->id();
            $table->string('title')->nullable();
            $table->text('description');
            $table->string('file')->nullable();
            $table->unsignedBigInteger('task_id');
            $table->foreign('task_id')->references('id')->on('hr_tasks')->onDelete('cascade');
            $table->unsignedBigInteger('employee_id');
            $table->foreign('employee_id','fk_employee_id')->references('id')->on('hr_employee')->onDelete('cascade');
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
        Schema::dropIfExists('hr_task_replies');
    }
}
