<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'paid_amount')) {
                $table->decimal('paid_amount', 12, 2)->default(0)->after('contract_value');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'remaining_amount')) {
                $table->decimal('remaining_amount', 12, 2)->default(0)->after('paid_amount');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'closure_client_iban')) {
                $table->string('closure_client_iban', 34)->nullable()->after('payment_receipt_path');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'closure_client_iban_attachment_path')) {
                $table->string('closure_client_iban_attachment_path')->nullable()->after('closure_client_iban');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contract_infos', 'closure_client_iban_attachment_path')) {
                $table->dropColumn('closure_client_iban_attachment_path');
            }

            if (Schema::hasColumn('rental_contract_infos', 'closure_client_iban')) {
                $table->dropColumn('closure_client_iban');
            }

            if (Schema::hasColumn('rental_contract_infos', 'remaining_amount')) {
                $table->dropColumn('remaining_amount');
            }

            if (Schema::hasColumn('rental_contract_infos', 'paid_amount')) {
                $table->dropColumn('paid_amount');
            }
        });
    }
};
