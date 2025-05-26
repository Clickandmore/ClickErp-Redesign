<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->string('visa_number')->nullable()->change();
            $table->date('visa_hijry_date')->nullable()->change();
            $table->date('visa_milad_date')->nullable()->change();
            $table->string('working_hours_num')->nullable()->change();
            $table->date('ingaz_from')->nullable()->change();
            $table->date('ingaz_to')->nullable()->change();
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
            $table->string('visa_number')->change();
            $table->date('visa_hijry_date')->change();
            $table->date('visa_milad_date')->change();
            $table->string('working_hours_num')->change();
            $table->date('ingaz_from')->change();
            $table->date('ingaz_to')->change();
        });
    }
}
