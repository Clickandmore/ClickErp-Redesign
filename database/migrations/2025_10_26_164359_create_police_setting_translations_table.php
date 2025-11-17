<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePoliceSettingTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('police_setting_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('police_setting_id')->unsigned();
            $table->string('locale')->index();
            $table->string('title_1');
            $table->string('title_2');
            $table->text('description_1');
            $table->text('description_2');
            $table->unique(['police_setting_id', 'locale']);
            $table->foreign('police_setting_id')->references('id')->on('police_settings')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('police_setting_translations');
    }
}
