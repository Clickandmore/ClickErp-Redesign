<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->string('moyasar_payment_id')->nullable()->after('payment_receipt_path');
            $table->string('moyasar_payment_url', 2048)->nullable()->after('moyasar_payment_id');
            $table->string('moyasar_payment_status')->nullable()->after('moyasar_payment_url');
            $table->timestamp('payment_link_sent_at')->nullable()->after('moyasar_payment_status');
            $table->timestamp('moyasar_paid_at')->nullable()->after('payment_link_sent_at');
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            $table->dropColumn([
                'moyasar_payment_id',
                'moyasar_payment_url',
                'moyasar_payment_status',
                'payment_link_sent_at',
                'moyasar_paid_at',
            ]);
        });
    }
};
