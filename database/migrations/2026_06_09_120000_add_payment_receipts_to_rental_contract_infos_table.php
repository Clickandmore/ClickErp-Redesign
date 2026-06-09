<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('rental_contract_infos')) {
            return;
        }

        if (!Schema::hasColumn('rental_contract_infos', 'payment_receipts')) {
            Schema::table('rental_contract_infos', function (Blueprint $table) {
                $table->json('payment_receipts')->nullable()->after('payment_receipt_path');
            });
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('rental_contract_infos')) {
            return;
        }

        if (Schema::hasColumn('rental_contract_infos', 'payment_receipts')) {
            Schema::table('rental_contract_infos', function (Blueprint $table) {
                $table->dropColumn('payment_receipts');
            });
        }
    }
};
