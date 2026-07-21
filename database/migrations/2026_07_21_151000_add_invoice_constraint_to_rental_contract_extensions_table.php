<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('rental_contract_extensions')
            || Schema::hasColumn('rental_contract_extensions', 'invoice_constraint_id')
        ) {
            return;
        }

        Schema::table('rental_contract_extensions', function (Blueprint $table) {
            $table->unsignedBigInteger('invoice_constraint_id')->nullable()->after('receipt_account_id');
        });
    }

    public function down(): void
    {
        if (
            Schema::hasTable('rental_contract_extensions')
            && Schema::hasColumn('rental_contract_extensions', 'invoice_constraint_id')
        ) {
            Schema::table('rental_contract_extensions', function (Blueprint $table) {
                $table->dropColumn('invoice_constraint_id');
            });
        }
    }
};
