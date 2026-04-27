<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientPollsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_client_polls', function (Blueprint $table) {
            $table->id();
            $table->foreignId('client_id')->constrained('crm_clients')->onDelete('cascade')->name('fk_poll_client_id');
            $table->foreignId('poll_id')->constrained('crm_polls')->onDelete('cascade')->name('fk_poll_id');
            $table->enum('status',['new','ended'])->default('new');
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
        Schema::dropIfExists('crm_client_polls');
    }
}
