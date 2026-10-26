<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        Schema::table('hourly_visits',function(Blueprint $t){$t->string('cash_collector_type',12)->default('auto');$t->unsignedBigInteger('cash_collector_worker_id')->nullable();});
        Schema::create('hourly_cash_collections',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('payment_id')->unique();$t->unsignedBigInteger('order_id')->index();$t->unsignedBigInteger('visit_id')->index();
            $t->string('holder_type',12);$t->unsignedBigInteger('holder_id');$t->decimal('amount',12,2);$t->decimal('remitted_amount',12,2)->default(0);
            $t->string('request_key',64)->unique();$t->timestamps();$t->index(['holder_type','holder_id']);
        });
        Schema::create('hourly_cash_remittances',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('collection_id')->index();$t->unsignedBigInteger('admin_id');$t->decimal('amount',12,2);
            $t->string('reference',255);$t->string('request_key',64)->unique();$t->timestamps();
        });
    }
    public function down(): void {
        Schema::dropIfExists('hourly_cash_remittances');Schema::dropIfExists('hourly_cash_collections');
        Schema::table('hourly_visits',fn(Blueprint $t)=>$t->dropColumn(['cash_collector_type','cash_collector_worker_id']));
    }
};
