<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPackageSnapshotToRentalExtensions extends Migration
{
    public function up()
    {
        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            $table->unsignedBigInteger('package_id')->nullable();
            $table->json('package_snapshot')->nullable();
            $table->json('pricing_snapshot')->nullable();
        });
    }

    public function down()
    {
        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            $table->dropColumn(['package_id', 'package_snapshot', 'pricing_snapshot']);
        });
    }
}
