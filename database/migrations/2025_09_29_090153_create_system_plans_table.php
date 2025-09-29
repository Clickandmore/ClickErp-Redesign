<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSystemPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('system_plans', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_subscription_id');
            $table->foreign('system_subscription_id')->references('id')->on('system_subscriptions')->onDelete('cascade')->onUpdate('cascade');
            $table->string('module_name');
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
        Schema::dropIfExists('system_plans');
    }
}
