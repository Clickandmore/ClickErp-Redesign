<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_service_incidents',function(Blueprint $t){
            $t->id();$t->uuid('request_token')->unique();$t->unsignedBigInteger('visit_id')->index();
            $t->unsignedBigInteger('maid_id')->index();$t->string('reporter_type',20);$t->unsignedBigInteger('reporter_id');
            $t->string('category',20);$t->text('note');$t->string('status',20)->default('pending')->index();
            $t->unsignedBigInteger('reviewed_by')->nullable();$t->text('review_reason')->nullable();
            $t->dateTime('unavailable_until')->nullable();$t->unsignedBigInteger('leave_id')->nullable();
            $t->dateTime('reviewed_at')->nullable();$t->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('hourly_service_incidents'); }
};
