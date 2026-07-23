<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('maid_status_change_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('maid_id')->constrained('maids')->cascadeOnDelete();
            $table->foreignId('office_id')->constrained('recruitment_offices')->cascadeOnDelete();
            $table->foreignId('requested_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->string('type', 30);
            $table->text('reason');
            $table->string('status', 30)->default('pending');
            $table->foreignId('reviewed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['maid_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maid_status_change_requests');
    }
};
