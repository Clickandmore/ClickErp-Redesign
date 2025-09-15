<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsAvailableForInstantToContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->boolean('is_available_for_instant')->default(false)->comment('متاحة للاستلام الفوري على الموقع');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->dropColumn('is_available_for_instant');
        });
    }
}
