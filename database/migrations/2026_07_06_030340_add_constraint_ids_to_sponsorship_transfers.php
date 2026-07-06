<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddConstraintIdsToSponsorshipTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->unsignedBigInteger('trial_start_constraint_id')->nullable()->after('visa_fee_amount');
            $table->unsignedBigInteger('completion_constraint_id')->nullable()->after('trial_start_constraint_id');
            $table->unsignedBigInteger('failure_constraint_id')->nullable()->after('completion_constraint_id');
        });
    }

    public function down()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropColumn(['trial_start_constraint_id', 'completion_constraint_id', 'failure_constraint_id']);
        });
    }
}
