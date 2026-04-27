<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateHrShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('hr_shifts', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->time('from_hour');
            $table->time('to_hour');
            $table->integer('month_days');
            $table->unsignedBigInteger('absence_sanctions_id');
            $table->foreign('absence_sanctions_id')->references('id')->on('hr_sanctions')->onDelete('cascade');
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
        Schema::dropIfExists('hr_shifts');
    }
}
