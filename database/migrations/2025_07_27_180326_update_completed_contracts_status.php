<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث العقود المكتملة من closed إلى completed
        DB::table('contracts')
            ->where('status', 'closed')
            ->update(['status' => 'completed']);

        // تحديث العقود تحت الضمان من under_warranty إلى completed مع تحديد حالة الضمان
        DB::table('contracts')
            ->where('status', 'under_warranty')
            ->update([
                'status' => 'completed',
                'warranty_status' => 'active'
            ]);

        // تحديث العقود المكتملة (arrival) إلى completed مع تحديد حالة الضمان
        DB::table('contracts')
            ->where('status', 'arrival')
            ->update([
                'status' => 'completed',
                'warranty_status' => 'active'
            ]);

        // تحديث العقود المكتملة (sari) إلى completed مع تحديد حالة الضمان
        DB::table('contracts')
            ->where('status', 'sari')
            ->update([
                'status' => 'completed',
                'warranty_status' => 'active'
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // لا يمكن التراجع عن هذا التحديث بأمان
        // لأننا لا نعرف الحالة الأصلية لكل عقد
    }
};
