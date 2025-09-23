<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateArrivalWorkTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('arrival_work_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('arrival_work_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('sub_title');
            $table->text('body');
            $table->unique(['arrival_work_id', 'locale']);
            $table->foreign('arrival_work_id')->references('id')->on('arrival_works')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('arrival_work_translations');
    }
}
