<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPreviousContractIdToContractsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreignId('previous_contract_id')->nullable()->after('id')->constrained('contracts')->onDelete('set null');
            $table->datetime('completed_at')->nullable()->after('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contracts', function (Blueprint $table) {
            $table->dropForeign(['previous_contract_id']);
            $table->dropColumn('previous_contract_id');
            $table->dropColumn('completed_at');
        });
    }
}
