<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRecruitmentOfficeTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('recruitment_office_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rec_office_id');
            $table->string('title');
            $table->string('locale');
            $table->unique(['rec_office_id', 'locale']);
            $table->foreign('rec_office_id')->references('id')->on('recruitment_offices')->onDelete('cascade');
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
        Schema::dropIfExists('recruitment_office_translations');
    }
}
