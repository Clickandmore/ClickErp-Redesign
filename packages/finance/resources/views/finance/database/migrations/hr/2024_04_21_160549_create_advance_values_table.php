<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAdvanceValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('advance_values', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('advance_id');
            $table->foreign('advance_id')->references('id')->on('advances')->onDelete('cascade');
            $table->double('amount')->default(0);
            $table->date('date');
            $table->string('status')->default('new');
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
        Schema::dropIfExists('advance_values');
    }
}
