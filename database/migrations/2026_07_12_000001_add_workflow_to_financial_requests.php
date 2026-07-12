<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('financial_requests', function (Blueprint $table) {
            if (! Schema::hasColumn('financial_requests', 'closed_at')) {
                $table->timestamp('closed_at')->nullable()->after('paid_at');
            }
        });

        if (! Schema::hasTable('financial_request_status_logs')) {
            Schema::create('financial_request_status_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('financial_request_id')->constrained()->cascadeOnDelete();
                $table->string('from_status')->nullable();
                $table->string('to_status');
                $table->unsignedBigInteger('performed_by_id')->nullable();
                $table->text('remarks')->nullable();
                $table->timestamps();
                $table->index(['financial_request_id', 'created_at'], 'fr_status_log_request_date_idx');
            });
        }

        DB::table('financial_requests')->where('status', 'pending_review')->update(['status' => 'for_approval']);
        DB::table('financial_requests')->where('status', 'in_progress')->update(['status' => 'approved']);
        DB::table('financial_requests')->where('status', 'rejected')->update(['status' => 'preparing']);
    }

    public function down(): void
    {
        Schema::dropIfExists('financial_request_status_logs');
        Schema::table('financial_requests', function (Blueprint $table) {
            if (Schema::hasColumn('financial_requests', 'closed_at')) {
                $table->dropColumn('closed_at');
            }
        });
    }
};
