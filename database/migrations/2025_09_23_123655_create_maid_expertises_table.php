<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaidExpertisesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maid_expertises', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('cascade');
            $table->string('number_of_years');
            $table->unsignedBigInteger('job_title_id');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade');
            $table->unsignedBigInteger('country_id');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
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
        Schema::dropIfExists('maid_expertises');
    }
}
