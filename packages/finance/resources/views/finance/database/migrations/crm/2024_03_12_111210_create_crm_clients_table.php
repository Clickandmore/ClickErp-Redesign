<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmClientsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_clients', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phone');
            $table->string('email')->nullable();
            $table->string('whatsapp',500)->default('#');
            $table->string('notes',1000)->nullable();
            $table->string('code')->nullable();
            $table->string('vat_number')->nullable();
            $table->unsignedBigInteger('social_contact_id')->nullable();
            $table->foreign('social_contact_id')->references('id')->on('crm_social_contacts')->onDelete('cascade');
            $table->unsignedBigInteger('city_id')->index()->nullable();
            $table->foreign('city_id')->references('id')->on('crm_areas')->onDelete('cascade');
            $table->unsignedBigInteger('area_id')->index()->nullable();
            $table->foreign('area_id')->references('id')->on('crm_areas')->onDelete('cascade');
            $table->boolean('status')->default(1);
            $table->string('address')->nullable('لا يوجد عنوان');
            $table->integer('admin_id')->unsigned()->index();
            $table->integer('client_id')->nullable()->unsigned()->index();

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
        Schema::dropIfExists('crm_clients');
    }
}
