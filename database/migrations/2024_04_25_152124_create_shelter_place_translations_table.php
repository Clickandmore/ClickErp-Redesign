<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShelterPlaceTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shelter_place_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('shelter_place_id');
            $table->string('title',1000);
            $table->string('locale');
            $table->unique(['shelter_place_id', 'locale']);
            $table->foreign('shelter_place_id')->references('id')->on('shelter_places')->onDelete('cascade');
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
        Schema::dropIfExists('shelter_place_translations');
    }
}
