<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\{Schema,DB};
return new class extends Migration {
    public function up() {
        Schema::table('hourly_rental_orders',function(Blueprint $t){$t->decimal('initial_amount',12,2)->nullable();$t->unsignedBigInteger('renewed_from_id')->nullable()->index();});
        DB::table('hourly_rental_orders')->update(['initial_amount'=>DB::raw('total_amount')]);
        Schema::table('hourly_visits',function(Blueprint $t){$t->unsignedBigInteger('team_leader_id')->nullable();$t->dateTime('delivery_at')->nullable();$t->dateTime('pickup_at')->nullable();$t->boolean('is_free')->default(false);});
        Schema::create('hourly_order_audits',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_rental_order_id')->index();$t->unsignedBigInteger('visit_id')->nullable();$t->unsignedBigInteger('admin_id')->nullable();$t->string('action');$t->text('reason')->nullable();$t->json('before')->nullable();$t->json('after')->nullable();$t->timestamps();});
        Schema::create('hourly_worker_reviews',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_rental_order_id')->index();$t->unsignedBigInteger('visit_id');$t->unsignedBigInteger('maid_id');$t->unsignedBigInteger('user_id');$t->unsignedTinyInteger('rating');$t->text('comment')->nullable();$t->timestamps();$t->unique(['visit_id','maid_id','user_id']);});
        Schema::create('hourly_order_installments',function(Blueprint $t){$t->id();$t->unsignedBigInteger('hourly_rental_order_id')->index();$t->date('due_date');$t->decimal('amount',12,2);$t->decimal('paid_amount',12,2)->default(0);$t->timestamps();});
    }
    public function down(){Schema::dropIfExists('hourly_order_installments');Schema::dropIfExists('hourly_worker_reviews');Schema::dropIfExists('hourly_order_audits');Schema::table('hourly_visits',fn(Blueprint $t)=>$t->dropColumn(['team_leader_id','delivery_at','pickup_at','is_free']));Schema::table('hourly_rental_orders',fn(Blueprint $t)=>$t->dropColumn(['initial_amount','renewed_from_id']));}
};
