<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissionVisionValueToAboutUsTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
   
          public function up(): void
    {
        Schema::table('about_us_translations', function (Blueprint $table) {
            $table->string('mission', 1000)->nullable()->after('description');
            $table->string('vision', 1000)->nullable()->after('mission');
            $table->string('value', 1000)->nullable()->after('vision');
         });
    }
    

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('about_us_translations', function (Blueprint $table) {
            //
        });
    }
}
