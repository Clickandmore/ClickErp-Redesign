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
            if (!Schema::hasColumn('rental_contracts', 'contract_sent_at')) {
                $table->timestamp('contract_sent_at')->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'contract_signed_at')) {
                $table->timestamp('contract_signed_at')->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'maid_delivered_at')) {
                $table->timestamp('maid_delivered_at')->nullable();
            }
            if (!Schema::hasColumn('rental_contracts', 'trial_started_at')) {
                $table->timestamp('trial_started_at')->nullable();
            }

            // إضافة حقول لتسجيل من قام بكل إجراء
            if (!Schema::hasColumn('rental_contracts', 'contract_sent_by')) {
                $table->unsignedBigInteger('contract_sent_by')->nullable();
                $table->foreign('contract_sent_by')->references('id')->on('admins')->onDelete('set null');
            }
            if (!Schema::hasColumn('rental_contracts', 'contract_signed_by')) {
                $table->unsignedBigInteger('contract_signed_by')->nullable();
                $table->foreign('contract_signed_by')->references('id')->on('admins')->onDelete('set null');
            }
            if (!Schema::hasColumn('rental_contracts', 'maid_delivered_by')) {
                $table->unsignedBigInteger('maid_delivered_by')->nullable();
                $table->foreign('maid_delivered_by')->references('id')->on('admins')->onDelete('set null');
            }
            if (!Schema::hasColumn('rental_contracts', 'trial_started_by')) {
                $table->unsignedBigInteger('trial_started_by')->nullable();
                $table->foreign('trial_started_by')->references('id')->on('admins')->onDelete('set null');
            }

            // إضافة حقل للتوقيع الإلكتروني للشركة (إذا لزم الأمر)
            if (!Schema::hasColumn('rental_contracts', 'company_signature')) {
                $table->text('company_signature')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contracts', function (Blueprint $table) {
            // حذف المفاتيح الخارجية أولاً
            if (Schema::hasColumn('rental_contracts', 'contract_sent_by')) {
                $table->dropForeign(['contract_sent_by']);
            }
            if (Schema::hasColumn('rental_contracts', 'contract_signed_by')) {
                $table->dropForeign(['contract_signed_by']);
            }
            if (Schema::hasColumn('rental_contracts', 'maid_delivered_by')) {
                $table->dropForeign(['maid_delivered_by']);
            }
            if (Schema::hasColumn('rental_contracts', 'trial_started_by')) {
                $table->dropForeign(['trial_started_by']);
            }

            // حذف الحقول
            $columns = [
                'contract_sent_at',
                'contract_signed_at',
                'maid_delivered_at',
                'trial_started_at',
                'contract_sent_by',
                'contract_signed_by',
                'maid_delivered_by',
                'trial_started_by',
                'company_signature'
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('rental_contracts', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
