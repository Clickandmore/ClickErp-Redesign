<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddVisaFeeToSponsorshipTransfers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->decimal('visa_fee_amount', 10, 2)->nullable()->after('failure_client_iban_attachment_path');
        });
    }

    public function down()
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropColumn('visa_fee_amount');
        });
    }
}
