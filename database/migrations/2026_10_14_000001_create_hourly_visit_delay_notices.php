<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('hourly_visit_delay_notices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('visit_id');
            $table->dateTime('scheduled_end_at');
            $table->string('stage', 20);
            $table->string('guard', 20);
            $table->unsignedBigInteger('staff_id');
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
            $table->unique(['visit_id', 'scheduled_end_at', 'stage', 'guard', 'staff_id'], 'hourly_delay_recipient_unique');
        });
    }
    public function down(): void { Schema::dropIfExists('hourly_visit_delay_notices'); }
};
