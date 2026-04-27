<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmFollowClientTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_follow_client', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('client_id');
            $table->foreign('client_id')->references('id')->on('crm_clients')->onDelete('cascade');
            $table->date('date');
            $table->string('content',1000);
            $table->string('notes',1000);
            $table->string('result',1000)->nullable();
            $table->foreignId('follow_clients_category_id')->constrained('crm_follow_clients_categories')->onDelete('cascade')->name('fk_fs_category_id');
            $table->integer('admin_id')->unsigned();
            $table->foreign('admin_id')->on('admins')->references('id')->onDelete('cascade')->name('fk_admin_id');

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
        Schema::dropIfExists('crm_follow_client');
    }
}
