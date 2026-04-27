<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCostCenterIdToConstraintDetailsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fn_constraint_details', function (Blueprint $table) {
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('creditor_value');
            $table->foreign('cost_center_id')->references('id')->on('fn_cost_centers')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fn_constraint_details', function (Blueprint $table) {
            $table->dropColumn('cost_center_id');
        });
    }
}
