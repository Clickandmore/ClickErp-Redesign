<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShelterDocumentMovements extends Migration
{
    public function up()
    {
        Schema::create('shelter_document_movements', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id')->index();
            $table->string('kind', 20);
            $table->string('action', 80);
            $table->unsignedBigInteger('template_id');
            $table->json('template_snapshot');
            $table->json('data');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('shelter_document_movements');
    }
}
