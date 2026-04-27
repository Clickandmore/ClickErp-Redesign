<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddCreatedByIdToFnConstraintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->unsignedBigInteger('created_by_id')->nullable()->after('year_id');
            $table->foreign('created_by_id')->references('id')->on('admins')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fn_constraints', function (Blueprint $table) {
            $table->dropColumn('created_by_id');
        });
    }
}
