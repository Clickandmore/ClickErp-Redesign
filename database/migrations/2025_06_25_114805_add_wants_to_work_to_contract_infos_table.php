<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddWantsToWorkToContractInfosTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('contract_infos', function (Blueprint $table) {
            $table->enum('wants_to_work', ['yes', 'no', 'unknown'])
                ->default('unknown')
                ->comment('رغبة العمل في عقود داخل الإيواء: نعم، لا، غير محدد');
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
            $table->dropColumn('wants_to_work');
        });
    }
}
