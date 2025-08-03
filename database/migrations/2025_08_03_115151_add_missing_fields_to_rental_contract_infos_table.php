<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingFieldsToRentalContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            // إضافة الأعمدة المفقودة
            $table->string('sponsorship_transfer')->nullable()->comment('نقل الكفالة (yes/no)');
            $table->string('rental_type')->nullable()->comment('نوع التأجير');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->dropColumn(['sponsorship_transfer', 'rental_type']);
        });
    }
}
