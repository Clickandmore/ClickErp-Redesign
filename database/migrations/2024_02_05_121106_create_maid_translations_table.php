<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMaidTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('maid_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id');
            $table->string('locale')->index();

            $table->string('languages',1000)->default('');
            $table->string('marital_status',255)->default('');

            $table->unique(['maid_id', 'locale']);
            $table->foreign('maid_id')->references('id')->on('maids')->onDelete('cascade');
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
        Schema::dropIfExists('maid_translations');
    }
}
