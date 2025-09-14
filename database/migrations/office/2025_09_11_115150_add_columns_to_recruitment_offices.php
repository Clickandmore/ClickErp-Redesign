<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnsToRecruitmentOffices extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recruitment_offices', function (Blueprint $table) {
            $table->string('phone_office')->after('nationality_id')->nullable();
            $table->string('license_number')->after('phone_office')->nullable();
            $table->string('street')->after('license_number')->nullable();
            $table->string('manager_name')->after('street')->nullable();
            $table->string('manager_phone')->after('manager_name')->nullable();
            $table->string('manager_email')->after('manager_phone')->nullable();
            $table->string('password')->after('manager_email')->nullable();
            $table->string('bank_address')->after('password')->nullable();
            $table->string('bank_name')->after('bank_address')->nullable();
            $table->string('bank_account_name')->after('bank_name')->nullable();
            $table->string('bank_swift_code')->after('bank_account_name')->nullable();
            $table->string('bank_iban')->after('bank_swift_code')->nullable();
            $table->string('bank_account')->after('bank_iban')->nullable();
            $table->string('cost_of_dollar')->after('bank_account')->nullable();
            $table->string('cost_of_other')->after('cost_of_dollar')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recruitment_offices', function (Blueprint $table) {
            //
        });
    }
}
