<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            // إضافة حقول workflow جديدة مثل نقل الكفالة
            $table->timestamp('contract_sent_at')->nullable()->after('signature');
            $table->timestamp('contract_signed_at')->nullable()->after('contract_sent_at');
            $table->timestamp('maid_delivered_at')->nullable()->after('contract_signed_at');
            $table->timestamp('trial_started_at')->nullable()->after('maid_delivered_at');

            // إضافة حقول لتسجيل من قام بكل إجراء
            $table->unsignedBigInteger('contract_sent_by')->nullable()->after('trial_started_at');
            $table->unsignedBigInteger('contract_signed_by')->nullable()->after('contract_sent_by');
            $table->unsignedBigInteger('maid_delivered_by')->nullable()->after('contract_signed_by');
            $table->unsignedBigInteger('trial_started_by')->nullable()->after('maid_delivered_by');

            // إضافة حقل للتوقيع الإلكتروني للشركة (إذا لزم الأمر)
            $table->text('company_signature')->nullable()->after('trial_started_by');

            // إضافة المفاتيح الخارجية
            $table->foreign('contract_sent_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('contract_signed_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('maid_delivered_by')->references('id')->on('admins')->onDelete('set null');
            $table->foreign('trial_started_by')->references('id')->on('admins')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            // حذف المفاتيح الخارجية أولاً
            $table->dropForeign(['contract_sent_by']);
            $table->dropForeign(['contract_signed_by']);
            $table->dropForeign(['maid_delivered_by']);
            $table->dropForeign(['trial_started_by']);

            // حذف الحقول
            $table->dropColumn([
                'contract_sent_at',
                'contract_signed_at',
                'maid_delivered_at',
                'trial_started_at',
                'contract_sent_by',
                'contract_signed_by',
                'maid_delivered_by',
                'trial_started_by',
                'company_signature'
            ]);
        });
    }
};
