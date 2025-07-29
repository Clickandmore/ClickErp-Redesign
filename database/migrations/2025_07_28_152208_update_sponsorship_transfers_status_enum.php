<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // تحديث enum لإضافة الحالات الجديدة
        DB::statement("ALTER TABLE sponsorship_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'trial', 'transferred', 'failed', 'rejected', 'completed') DEFAULT 'pending'");

        // إضافة أعمدة جديدة لتتبع عملية النقل
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->timestamp('trial_started_at')->nullable()->after('approved_at');
            $table->timestamp('transferred_at')->nullable()->after('trial_started_at');
            $table->timestamp('failed_at')->nullable()->after('transferred_at');
            $table->text('failure_reason')->nullable()->after('failed_at');
            $table->unsignedBigInteger('transferred_by')->nullable()->after('failure_reason');
            $table->unsignedBigInteger('failed_by')->nullable()->after('transferred_by');

            // Foreign keys
            $table->foreign('transferred_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('failed_by')->references('id')->on('admins')->onDelete('set null');

            // Indexes for performance (تجنب إضافة status index لأنه موجود)
            $table->index('trial_started_at');
            $table->index('transferred_at');
            $table->index('failed_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropForeign(['transferred_by']);
            $table->dropForeign(['failed_by']);
            $table->dropIndex(['trial_started_at']);
            $table->dropIndex(['transferred_at']);
            $table->dropIndex(['failed_at']);
            $table->dropColumn([
                'trial_started_at',
                'transferred_at',
                'failed_at',
                'failure_reason',
                'transferred_by',
                'failed_by'
            ]);
        });

        // إعادة enum إلى الحالة السابقة
        DB::statement("ALTER TABLE sponsorship_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'rejected', 'completed') DEFAULT 'pending'");
    }
};
