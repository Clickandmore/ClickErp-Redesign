<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFileToHrEmployeeVacationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hr_employee_vacations', function (Blueprint $table) {
            $table->string('vacation_file',500)->nullable()->after('value');
            $table->string('return_file',500)->nullable()->after('vacation_file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hr_employee_vacations', function (Blueprint $table) {
            $table->dropColumn(['vacation_file','return_file']);
        });
    }
}
