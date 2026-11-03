<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** إشعار العميل يحمل طلب الساعات وزيارته ليعرض رقم الطلب ويفتحه (order_id يخص طلبات الاستقدام). */
return new class extends Migration {
    public function up(): void {
        Schema::table('notifications',function(Blueprint $t){
            if(!Schema::hasColumn('notifications','hourly_order_id'))$t->unsignedBigInteger('hourly_order_id')->nullable()->index();
            if(!Schema::hasColumn('notifications','hourly_visit_id'))$t->unsignedBigInteger('hourly_visit_id')->nullable();
        });
    }
    public function down(): void {
        Schema::table('notifications',fn(Blueprint $t)=>$t->dropColumn(['hourly_order_id','hourly_visit_id']));
    }
};
