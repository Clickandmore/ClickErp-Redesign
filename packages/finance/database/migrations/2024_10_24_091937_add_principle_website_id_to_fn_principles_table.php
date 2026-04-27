<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPrincipleWebsiteIdToFnPrinciplesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->foreignId('principle_website_id')->nullable()->after('perished_account_id')->constrained('fn_principle_websites')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->dropColumn('principle_website_id');
        });
    }
}
