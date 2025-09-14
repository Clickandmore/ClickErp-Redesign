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
        // إضافة الحالات الجديدة لنقل الكفالة
        DB::statement("ALTER TABLE sponsorship_transfers MODIFY COLUMN status ENUM('pending', 'waiting_contract_send', 'contract_sent', 'contract_signed', 'maid_delivered', 'trial_started', 'approved', 'trial', 'transferred', 'failed', 'rejected', 'completed') DEFAULT 'pending'");

        // إضافة الأعمدة الجديدة لتتبع العملية
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->timestamp('contract_sent_at')->nullable()->after('approved_at')->comment('تاريخ إرسال العقد');
            $table->timestamp('contract_signed_at')->nullable()->after('contract_sent_at')->comment('تاريخ توقيع العقد');
            $table->timestamp('maid_delivered_at')->nullable()->after('contract_signed_at')->comment('تاريخ تسليم العاملة');
            $table->unsignedBigInteger('contract_sent_by')->nullable()->after('maid_delivered_at')->comment('من أرسل العقد');
            $table->unsignedBigInteger('contract_signed_by')->nullable()->after('contract_sent_by')->comment('من سجل توقيع العقد');
            $table->unsignedBigInteger('maid_delivered_by')->nullable()->after('contract_signed_by')->comment('من سجل تسليم العاملة');

            // Foreign keys
            $table->foreign('contract_sent_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('contract_signed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('maid_delivered_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            $table->dropForeign(['contract_sent_by']);
            $table->dropForeign(['contract_signed_by']);
            $table->dropForeign(['maid_delivered_by']);
            $table->dropColumn([
                'contract_sent_at',
                'contract_signed_at',
                'maid_delivered_at',
                'contract_sent_by',
                'contract_signed_by',
                'maid_delivered_by'
            ]);
        });

        // إعادة enum إلى الحالة السابقة
        DB::statement("ALTER TABLE sponsorship_transfers MODIFY COLUMN status ENUM('pending', 'approved', 'trial', 'transferred', 'failed', 'rejected', 'completed') DEFAULT 'pending'");
    }
};
