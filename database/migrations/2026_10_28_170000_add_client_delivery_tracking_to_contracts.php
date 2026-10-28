<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddClientDeliveryTrackingToContracts extends Migration
{
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dateTime('client_delivered_at')->nullable()->index();
            $table->unsignedBigInteger('client_delivery_recorded_by')->nullable();
            $table->text('client_delivery_note')->nullable();
        });
    }

    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropColumn(['client_delivered_at', 'client_delivery_recorded_by', 'client_delivery_note']);
        });
    }
}