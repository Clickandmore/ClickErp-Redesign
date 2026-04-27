<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCrmClientsAttachmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('crm_clients_attachments', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('attachment_id')->constrained('crm_attachment_categories')->onDelete('cascade')->name('fk_attachment_id');
            $table->foreignId('client_id')->constrained('crm_clients')->onDelete('cascade');
            $table->string('file')->default('');
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
        Schema::dropIfExists('crm_clients_attachments');
    }
}
