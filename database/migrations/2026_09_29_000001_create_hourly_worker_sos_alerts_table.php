<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('hourly_worker_sos_alerts')) return;

        Schema::create('hourly_worker_sos_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maid_id')->index();
            $table->unsignedBigInteger('hourly_visit_id')->nullable()->index();
            $table->string('status', 20)->default('triggered')->index();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->decimal('accuracy', 8, 2)->nullable();
            $table->string('message', 500)->nullable();
            $table->unsignedInteger('repeat_count')->default(1);
            $table->unsignedBigInteger('acknowledged_by')->nullable();
            $table->dateTime('acknowledged_at')->nullable();
            $table->unsignedBigInteger('resolved_by')->nullable();
            $table->dateTime('resolved_at')->nullable();
            $table->string('resolution_note', 500)->nullable();
            $table->dateTime('cancelled_at')->nullable();
            $table->timestamps();
            $table->index(['maid_id', 'status']);
            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('hourly_worker_sos_alerts');
    }
};
