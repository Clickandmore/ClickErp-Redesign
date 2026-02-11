<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateContractNotesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('contract_notes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contract_id');
            $table->unsignedBigInteger('admin_id');
            $table->text('note');
            $table->timestamps();

            $table->foreign('contract_id')
                  ->references('id')
                  ->on('contracts')
                  ->onDelete('cascade');

            $table->foreign('admin_id')
                  ->references('id')
                  ->on('admins')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('contract_notes');
    }
}
