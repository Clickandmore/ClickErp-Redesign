<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProfessionalApplicationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('professional_applications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('facility_name');
            $table->string('phone');
            $table->string('number_of_workers_required');
            $table->unsignedBigInteger('job_title_id')->nullable();
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade')->onUpdate('cascade');
            $table->string('visa_number');
            $table->string('other_requirement');
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
        Schema::dropIfExists('professional_applications');
    }
}
