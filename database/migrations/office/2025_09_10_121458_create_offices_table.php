<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOfficesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('offices', function (Blueprint $table) {
            $table->id();
            $table->string('phone_office');
            $table->string('license_number');
            $table->unsignedBigInteger('nationality_id');
            $table->foreign('nationality_id')->references('id')->on('nationalities')->onDelete('cascade')->onUpdate('cascade');
            $table->string('street');
            $table->string('manager_name');
            $table->string('manager_phone');
            $table->string('manager_email');
            $table->string('password');
            $table->string('bank_address');
            $table->string('bank_name');
            $table->string('bank_account_name');
            $table->string('bank_swift_code');
            $table->string('bank_iban');
            $table->string('bank_account');
            $table->string('cost_of_dollar');
            $table->string('cost_of_other');
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
        Schema::dropIfExists('offices');
    }
}
