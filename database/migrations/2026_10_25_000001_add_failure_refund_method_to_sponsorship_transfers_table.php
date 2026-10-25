<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            // طريقة سداد مبلغ الاسترجاع عند فشل التجربة: bank (تحويل بنكي) أو cash (نقدي)
            if (!Schema::hasColumn('sponsorship_transfers', 'failure_refund_method')) {
                $table->string('failure_refund_method', 20)->nullable()->after('failure_settlement_amount');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sponsorship_transfers', function (Blueprint $table) {
            if (Schema::hasColumn('sponsorship_transfers', 'failure_refund_method')) {
                $table->dropColumn('failure_refund_method');
            }
        });
    }
};
