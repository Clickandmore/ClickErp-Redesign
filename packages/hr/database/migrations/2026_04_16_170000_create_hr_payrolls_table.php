<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrPayrollsTable extends Migration
{
    public function up()
    {
        Schema::create('hr_payrolls', function (Blueprint $table) {
            $table->id();
            $table->string('month_year')->unique();
            $table->unsignedInteger('month_days')->default(30);
            $table->unsignedInteger('workers_count')->default(0);
            $table->double('total_gross_salary')->default(0);
            $table->double('total_deductions')->default(0);
            $table->double('total_net_salary')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('hr_payrolls');
    }
}
