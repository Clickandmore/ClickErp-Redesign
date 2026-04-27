<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsCompanyToFnClients extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fn_clients', function (Blueprint $table) {
            $table->boolean('is_company')->default(false)->nullable()->after('created_by_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fn_clients', function (Blueprint $table) {
            $table->dropColumn('is_company');
        });
    }
}
