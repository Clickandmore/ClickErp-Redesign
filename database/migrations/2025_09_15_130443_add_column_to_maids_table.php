<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnToMaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->string('st_code')->default('#')->nullable()->after('id');
            $table->string('int_code')->default('#')->nullable()->after('st_code');
            $table->integer('duration')->default(0)->nullable()->after('int_code');
            $table->string('video_preview')->default('#')->nullable()->after('cv_file');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropColumn('st_code');
            $table->dropColumn('int_code');
            $table->dropColumn('duration');
            $table->dropColumn('video_preview');
        });
    }
}
