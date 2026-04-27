<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmPriceOffersDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_price_offers_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('price_offer_id');
            $table->foreign('price_offer_id')->references('id')->on('crm_price_offers')->onDelete('cascade');
            $table->unsignedBigInteger('service_id');
//            $table->foreign('service_id')->references('id')->on('crm_services')->onDelete('cascade');
            $table->double('amount')->default(1);
            $table->double('price')->default(0);
            $table->double('discount')->default('0');
            $table->string('notes',1000)->default('');
            $table->text('desc')->nullable();
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
        Schema::dropIfExists('crm_price_offers_details');
    }
}
