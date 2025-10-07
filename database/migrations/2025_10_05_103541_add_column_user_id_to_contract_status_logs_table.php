<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddColumnUserIdToContractStatusLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
       Schema::table('contract_status_logs', function (Blueprint $table) {
            if (!Schema::hasColumn('contract_status_logs', 'user_id')) {
                $table->foreignId('user_id')
                      ->nullable() // السماح بقيمة NULL
                      ->constrained('users')
                      ->nullOnDelete(); // بدل cascade، يخليها NULL لو المستخدم اتحذف
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('contract_status_logs', function (Blueprint $table) {
            if (Schema::hasColumn('contract_status_logs', 'user_id')) {
                $table->dropForeign(['user_id']);
                $table->dropColumn('user_id');
            }
        });
    }
}
