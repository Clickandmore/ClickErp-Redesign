<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShelterDocumentTemplates extends Migration
{
    public function up()
    {
        Schema::create('shelter_document_templates', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 20);
            $table->string('company_name');
            $table->string('title');
            $table->text('declaration');
            $table->text('footer')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamps();
        });
        Schema::create('shelter_documents', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->string('kind', 20);
            $table->unsignedBigInteger('template_id');
            $table->boolean('enabled')->default(true);
            $table->json('template_snapshot');
            $table->json('data');
            $table->timestamps();
            $table->unique(['contract_id', 'kind']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('shelter_documents');
        Schema::dropIfExists('shelter_document_templates');
    }
}
