<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToSettingsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->string('qr_image')->default('#')->after('map_link');
            $table->string('second_qr_image')->default('#')->after('qr_image');
            $table->string('experience_image')->default('#')->after('second_qr_image');
            $table->boolean('customer_services')->default(false)->after('experience_image');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('settings', function (Blueprint $table) {
            $table->dropColumn('qr_image');
            $table->dropColumn('second_qr_image');
            $table->dropColumn('experience_image');
            $table->dropColumn('customer_services');
        });
    }
}
