<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateFnBondChecksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('fn_bond_checks', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bond_id');
            $table->foreign('bond_id')->references('id')->on('fn_bonds')->onDelete('cascade');
            $table->string("check_num",255);
            $table->double("check_value",255);
            $table->date("check_date");
            $table->tinyInteger("status")->default(0);// enum class
            $table->unsignedBigInteger('created_by_id');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
            $table->unsignedBigInteger('year_id')->nullable();
            $table->foreign('year_id')->references('id')->on('fn_years')->onDelete('cascade');
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
        Schema::dropIfExists('fn_bond_checks');
    }
}
