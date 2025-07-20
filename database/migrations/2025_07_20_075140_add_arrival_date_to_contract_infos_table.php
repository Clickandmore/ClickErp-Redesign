<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddArrivalDateToContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->date('arrival_date')->nullable()->comment('تاريخ الوصول');
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
            $table->dropColumn('arrival_date');
        });
    }
}
