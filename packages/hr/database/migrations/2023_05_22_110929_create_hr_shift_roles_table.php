<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrShiftRolesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_shift_roles', function (Blueprint $table) {
            $table->id();
            $table->integer('minutes');
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('hr_shifts')->onDelete('cascade');
            $table->unsignedBigInteger('sanctions_id');
            $table->foreign('sanctions_id')->references('id')->on('hr_sanctions')->onDelete('cascade');
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
        Schema::dropIfExists('hr_shift_roles');
    }
}
