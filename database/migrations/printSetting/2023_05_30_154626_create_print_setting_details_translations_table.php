<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePrintSettingDetailsTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('print_details_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('print_details_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('title_value');

            $table->unique(['print_details_id', 'locale']);
            $table->foreign('print_details_id')->references('id')->on('print_setting_details')->onDelete('cascade');
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
        Schema::dropIfExists('print_details_translations');
    }
}
