<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrintSettingDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('print_setting_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('print_setting_id');
            $table->enum('type',['first','second']);

            $table->foreign('print_setting_id')->references('id')->on('print_setting')->onDelete('cascade');
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
        Schema::dropIfExists('print_setting_details');
    }
}
