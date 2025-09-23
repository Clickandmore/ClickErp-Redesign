<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRenewContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('renew_contracts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade')->onUpdate('cascade');
            $table->string('status')->nullable()->default('pending');
            $table->string('client_name');
            $table->string('client_phone');
            $table->string('client_address');
            $table->string('city');
            $table->string('worker_name');
            $table->string('worker_phone');
            $table->string('worker_address');
            $table->string('passport_number');
            $table->string('name_of_relative');
            $table->string('address_of_relative');
            $table->string('phone_of_relative');
            $table->unsignedBigInteger('nationality_id')->nullable();
            $table->foreign('nationality_id')->references('id')->on('nationalities')->onDelete('cascade')->onUpdate('cascade');
            $table->string('worker_new_salary');
            $table->string('worker_old_salary');
            $table->string('worker_brought_from');
            $table->string('worker_marital_status');
            $table->string('sponsor_card');
            $table->string('worker_residence');
            $table->string('first_page_of_the_worker_passport');
            $table->string('travel_tickets');
            $table->string('visa_printed_in_the_worker_passport');
            $table->string('exit_and_re_entry_visa');
            $table->string('customer_national_address');
            $table->string('worker_information');
            $table->string('image_of_bank_transfer');
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
        Schema::dropIfExists('renew_contracts');
    }
}
