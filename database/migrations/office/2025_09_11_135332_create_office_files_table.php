<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficeFilesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('office_files', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('recruitment_office_id');
            $table->foreign('recruitment_office_id')->references('id')->on('recruitment_offices')->onDelete('cascade')->onUpdate('cascade');
            $table->string('office_file_name');
            $table->unsignedBigInteger('job_title_id');
            $table->foreign('job_title_id')->references('id')->on('job_titles')->onDelete('cascade')->onUpdate('cascade');
            $table->unsignedBigInteger('religion_id');
            $table->foreign('religion_id')->references('id')->on('religions')->onDelete('cascade')->onUpdate('cascade');
            $table->string('marital_status');
            $table->string('contract_duration');
            $table->string('passport_validity_period');
            $table->string('salary');
            $table->string('recruitment_price');
            $table->json('languages');
            $table->json('passport_issuance_location');
            $table->json('education');
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
        Schema::dropIfExists('office_files');
    }
}
