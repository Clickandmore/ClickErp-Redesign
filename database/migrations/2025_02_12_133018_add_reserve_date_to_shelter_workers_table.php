<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReserveDateToShelterWorkersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shelter_workers', function (Blueprint $table) {
            $table->date('reserve_date')->nullable()->after('reserve');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shelter_workers', function (Blueprint $table) {
            $table->dropColumn('reserve_date');
        });
    }
}
