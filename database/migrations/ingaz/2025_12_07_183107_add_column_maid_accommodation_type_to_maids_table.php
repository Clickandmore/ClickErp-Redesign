<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnMaidAccommodationTypeToMaidsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->tinyInteger('maid_accommodation_type')->default(1)->after('status')->comment('1 => سيرة حجز, 2 = داخل الايواء');
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
            $table->dropColumn('maid_accommodation_type');
        });
    }
}
