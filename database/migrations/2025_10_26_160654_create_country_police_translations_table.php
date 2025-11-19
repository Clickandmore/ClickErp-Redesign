<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCountryPoliceTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('country_police_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('country_police_id')->unsigned();
            $table->string('locale')->index();
            $table->string('name');
            $table->string('text_file_1');
            $table->string('text_file_2');
            $table->unique(['country_police_id', 'locale']);
            $table->foreign('country_police_id')->references('id')->on('country_polices')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('country_police_translations');
    }
}
