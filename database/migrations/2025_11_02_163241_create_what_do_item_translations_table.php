<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatDoItemTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('what_do_item_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('what_do_item_id')->unsigned();
            $table->string('locale')->index();
            $table->string('title');
            $table->unique(['what_do_item_id', 'locale']);
            $table->foreign('what_do_item_id')->references('id')->on('what_do_items')->onDelete('cascade');
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
        Schema::dropIfExists('what_do_item_translations');
    }
}
