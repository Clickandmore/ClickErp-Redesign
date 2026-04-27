<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrPayrollItemsTable extends Migration
{
    public function up()
    {
        Schema::create('hr_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('payroll_id');
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('maid_id')->nullable();
            $table->string('employee_name');
            $table->string('nationality_name')->nullable();
            $table->double('basic_salary')->default(0);
            $table->double('work_days')->default(0);
            $table->double('absence_days')->default(0);
            $table->double('incentive')->default(0);
            $table->double('other_additions')->default(0);
            $table->double('gross_salary')->default(0);
            $table->double('advance')->default(0);
            $table->double('penalty')->default(0);
            $table->double('other_deductions')->default(0);
            $table->double('total_deductions')->default(0);
            $table->double('net_salary')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payroll_id')
                ->references('id')
                ->on('hr_payrolls')
                ->onDelete('cascade');

            $table->foreign('employee_id')
                ->references('id')
                ->on('hr_employee')
                ->onDelete('cascade');

            $table->unique(['payroll_id', 'employee_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_payroll_items');
    }
}
