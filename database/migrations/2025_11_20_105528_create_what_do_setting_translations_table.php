<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhatDoSettingTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('what_do_setting_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('what_do_set_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description');
            $table->unique(['what_do_set_id', 'locale']);
            $table->foreign('what_do_set_id')->references('id')->on('what_do_settings')->onDelete('cascade')->onUpdate('cascade');

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
        Schema::dropIfExists('what_do_setting_translations');
    }
}
