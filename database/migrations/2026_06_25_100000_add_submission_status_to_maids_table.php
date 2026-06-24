<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            // null = أضافه الأدمن مباشرةً (لا يحتاج مراجعة)
            // pending_review = رفعه مكتب خارجي ويحتاج موافقة
            // approved = وافق عليه الأدمن
            // rejected = رفضه الأدمن
            $table->string('submission_status')->nullable()->after('is_show');
            $table->text('rejection_reason')->nullable()->after('submission_status');
            $table->unsignedBigInteger('reviewed_by')->nullable()->after('rejection_reason');
            $table->timestamp('reviewed_at')->nullable()->after('reviewed_by');

            $table->foreign('reviewed_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('maids', function (Blueprint $table) {
            $table->dropForeign(['reviewed_by']);
            $table->dropColumn(['submission_status', 'rejection_reason', 'reviewed_by', 'reviewed_at']);
        });
    }
};
