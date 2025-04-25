<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddFeildsToContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->date('maid_date_back')
                ->nullable()
                ->comment('تاريخ ارجاع العاملة');
            $table->string('salary_final_status')
                ->nullable()
                ->comment('الحالة النهائية للراتب');
            $table->string('duration_from_enter')
                ->nullable()
                ->comment('المدة منذ دخول المملكة');
            $table->string('back_reasons',1000)
                ->nullable()
                ->comment('اسباب ارجاع العاملة');

            $table->unsignedBigInteger('shelter_id')->nullable();
            $table->foreign('shelter_id')
                ->references('id')
                ->on('shelter_places')
                ->onDelete('cascade');

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
            //
        });
    }
}
