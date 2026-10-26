<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
    public function up(): void {
        if(Schema::hasTable('hourly_service_time_checks'))return;
        Schema::create('hourly_service_time_checks',function(Blueprint $t){
            $t->id();$t->unsignedBigInteger('order_id')->index();$t->unsignedBigInteger('visit_id')->index();
            $t->unsignedBigInteger('worker_visit_id');$t->unsignedBigInteger('maid_id')->index();
            $t->dateTime('started_at');$t->dateTime('expected_end_at');$t->boolean('started_from_schedule')->default(false);
            $t->string('status',30)->default('reminded')->index();
            $t->dateTime('reminded_at')->nullable();$t->dateTime('overrun_notified_at')->nullable();
            $t->string('reason',40)->nullable();$t->text('worker_note')->nullable();
            $t->dateTime('claimed_finished_at')->nullable();$t->dateTime('reason_reported_at')->nullable();
            $t->dateTime('finish_reported_at')->nullable();
            $t->string('decision',40)->nullable();$t->text('decision_note')->nullable();
            $t->unsignedBigInteger('reviewed_by')->nullable();$t->dateTime('reviewed_at')->nullable();$t->timestamps();
            $t->unique(['worker_visit_id','expected_end_at'],'hourly_service_time_worker_end_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('hourly_service_time_checks'); }
};
