<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFreezeReleasesMaidToRentalContracts extends Migration
{
    public function up()
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->boolean('freeze_releases_maid')->default(false);
        });
    }

    public function down()
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            $table->dropColumn('freeze_releases_maid');
        });
    }
}
