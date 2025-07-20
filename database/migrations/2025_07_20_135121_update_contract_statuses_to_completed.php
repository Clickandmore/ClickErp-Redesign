<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateContractStatusesToCompleted extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // تحديث العقود تحت الضمان والمكتملة إلى completed
        DB::table('contracts')
            ->whereIn('status', ['under_warranty', 'closed', 'arrival'])
            ->update(['status' => 'completed']);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // لا يمكن التراجع عن هذا التحديث بأمان
        // لأننا لا نعرف الحالة الأصلية لكل عقد
    }
}
