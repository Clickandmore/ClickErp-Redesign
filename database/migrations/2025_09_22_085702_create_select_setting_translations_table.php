<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSelectSettingTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('select_setting_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('select_setting_id')->unsigned();
            $table->string('locale')->index();
            $table->string('body');
            $table->unique(['select_setting_id', 'locale']);
            $table->foreign('select_setting_id')->references('id')->on('select_settings')->onDelete('cascade');
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
        Schema::dropIfExists('select_setting_translations');
    }
}
