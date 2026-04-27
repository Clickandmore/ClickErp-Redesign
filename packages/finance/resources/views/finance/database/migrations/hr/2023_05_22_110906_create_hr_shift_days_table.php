<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrShiftDaysTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_shift_days', function (Blueprint $table) {
            $table->id();
            $table->string('days');
            $table->unsignedBigInteger('shift_id');
            $table->foreign('shift_id')->references('id')->on('hr_shifts')->onDelete('cascade');
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
        Schema::dropIfExists('hr_shift_days');
    }
}
