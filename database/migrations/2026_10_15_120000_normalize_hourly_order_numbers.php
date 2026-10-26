<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * توحيد أرقام طلبات الساعات القديمة (HR-<UUID>) على صيغة الطلبات الجديدة HR-00000038 (المعرّف بثماني خانات).
 * الرقم القديم يُحفظ في legacy_order_number ليبقى البحث به ممكنًا (رسائل وروابط أُرسلت للعملاء سابقًا).
 * رقم الفاتورة (invoice_number) لا يُمس: الفواتير الصادرة لا تُعاد ترقيمتها.
 */
class NormalizeHourlyOrderNumbers extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('hourly_rental_orders')) return;
        if (!Schema::hasColumn('hourly_rental_orders', 'legacy_order_number')) {
            Schema::table('hourly_rental_orders', function (Blueprint $table) {
                $table->string('legacy_order_number')->nullable()->index()->after('order_number');
            });
        }

        DB::table('hourly_rental_orders')->select(['id', 'order_number'])->orderBy('id')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                $number = 'HR-'.str_pad((string) $order->id, 8, '0', STR_PAD_LEFT);
                if ($order->order_number === $number) continue;
                DB::table('hourly_rental_orders')->where('id', $order->id)
                    ->update(['legacy_order_number' => $order->order_number, 'order_number' => $number]);
            }
        });
    }

    public function down()
    {
        if (!Schema::hasColumn('hourly_rental_orders', 'legacy_order_number')) return;
        DB::table('hourly_rental_orders')->whereNotNull('legacy_order_number')->orderBy('id')->chunkById(500, function ($orders) {
            foreach ($orders as $order) {
                DB::table('hourly_rental_orders')->where('id', $order->id)->update(['order_number' => $order->legacy_order_number]);
            }
        });
        Schema::table('hourly_rental_orders', fn (Blueprint $table) => $table->dropColumn('legacy_order_number'));
    }
}
