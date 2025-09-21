<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelectWorkerTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('select_worker_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('select_worker_id')->unsigned();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['select_worker_id', 'locale']);
            $table->foreign('select_worker_id')->references('id')->on('select_workers')->onDelete('cascade');
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
        Schema::dropIfExists('select_worker_translations');
    }
}
