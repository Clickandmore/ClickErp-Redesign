<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ChangeValueTypeAndAddNewColumnToHrTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_sanctions', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_insurances', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_tax', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_rewards', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });

        Schema::table('hr_employee_sanctions', function (Blueprint $table) {
            $table->decimal('value', 10, 2)->change();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('hr_allowances', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_sanctions', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_insurances', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_tax', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_rewards', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_employee_allowances', function (Blueprint $table) {
            $table->integer('value')->change();
        });

        Schema::table('hr_employee_sanctions', function (Blueprint $table) {
            $table->integer('value')->change();
        });
    }
}
