<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToMaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->integer('childrens_count')->default(0)->nullable()->after('weight');
            $table->string('education')->default('low')->nullable()->after('childrens_count');
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
            $table->dropColumn('childrens_count');
            $table->dropColumn('education');
        });
    }
}
