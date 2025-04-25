<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrintSettingTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('print_setting_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('print_setting_id');
            $table->string('locale')->index();
            $table->string('first_title');
            $table->string('second_title');

            $table->unique(['print_setting_id', 'locale']);
            $table->foreign('print_setting_id')->references('id')->on('print_setting')->onDelete('cascade');
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
        Schema::dropIfExists('print_setting_translations');
    }
}
