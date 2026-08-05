<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddImageFileToFeaturesTable extends Migration
{
    public function up()
    {
        Schema::table('features', function (Blueprint $table) {
            $table->string('image_file')->nullable()->after('image');
        });
    }

    public function down()
    {
        Schema::table('features', function (Blueprint $table) {
            $table->dropColumn('image_file');
        });
    }
}
