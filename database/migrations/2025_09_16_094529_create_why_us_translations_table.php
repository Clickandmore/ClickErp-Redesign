<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWhyUsTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('why_us_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('why_us_id');
            $table->string('locale')->index();
            $table->string('title');
            $table->text('description');
            $table->unique(['why_us_id', 'locale']);
            $table->foreign('why_us_id')->references('id')->on('why_us')->onDelete('cascade')->onUpdate('cascade');
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
        Schema::dropIfExists('why_us_translations');
    }
}
