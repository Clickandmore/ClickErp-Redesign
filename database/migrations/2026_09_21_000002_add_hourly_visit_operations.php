<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(){
        Schema::table('hourly_visits',function(Blueprint $t){$t->boolean('offered_to_workers')->default(false)->index();});
        Schema::create('hourly_visit_batches',function(Blueprint $t){
            $t->uuid('id')->primary();$t->unsignedBigInteger('admin_id');$t->string('action');$t->string('payload_hash',64);$t->json('result')->nullable();$t->timestamps();
        });
        Schema::create('hourly_visit_receipts',function(Blueprint $t){
            $t->id();$t->uuid('batch_id');$t->unsignedBigInteger('user_id');$t->decimal('amount',12,2);$t->json('details');$t->string('method');$t->dateTime('paid_at');$t->text('description')->nullable();$t->timestamps();$t->unique(['batch_id','user_id']);
        });
        Schema::create('hourly_visit_notices',function(Blueprint $t){
            $t->id();$t->uuid('batch_id');$t->unsignedBigInteger('user_id');$t->string('channel');$t->string('title');$t->text('body');$t->string('status')->default('pending');$t->text('error')->nullable();$t->timestamps();$t->unique(['batch_id','user_id','channel']);
        });
    }
    public function down(){Schema::dropIfExists('hourly_visit_notices');Schema::dropIfExists('hourly_visit_receipts');Schema::dropIfExists('hourly_visit_batches');Schema::table('hourly_visits',fn(Blueprint $t)=>$t->dropColumn('offered_to_workers'));}
};
