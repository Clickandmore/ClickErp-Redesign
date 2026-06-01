<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaidRecruitmentProceduresTable extends Migration
{
    public function up()
    {
        Schema::create('maid_recruitment_procedures', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id')->unique();
            $table->unsignedBigInteger('contract_id')->nullable();
            $table->date('applied_at')->nullable();
            $table->string('medical_status')->nullable()->default('pending');
            $table->date('medical_date')->nullable();
            $table->string('tesda_status')->nullable();
            $table->date('tesda_date')->nullable();
            $table->string('owwa_status')->nullable();
            $table->date('owwa_date')->nullable();
            $table->string('oec_payment_status')->nullable();
            $table->date('oec_payment_date')->nullable();
            $table->string('oec_release_status')->nullable();
            $table->date('oec_release_date')->nullable();
            $table->string('embassy_stamping_status')->nullable();
            $table->date('embassy_stamping_date')->nullable();
            $table->string('poea_stamping_status')->nullable();
            $table->date('poea_stamping_date')->nullable();
            $table->string('ticket_payment_status')->nullable();
            $table->date('ticket_payment_date')->nullable();
            $table->string('ticket_purchase_status')->nullable();
            $table->date('ticket_purchase_date')->nullable();
            $table->text('remarks')->nullable();
            $table->string('current_stage')->nullable()->default('medical_pending');
            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('cascade');
            $table->foreign('contract_id')->references('id')->on('contracts')->onDelete('set null');
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('maid_recruitment_procedures');
    }
}
