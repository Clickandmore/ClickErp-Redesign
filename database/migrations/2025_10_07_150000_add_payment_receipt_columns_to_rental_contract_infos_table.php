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
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_infos', 'package_id')) {
                $table->foreignId('package_id')->nullable()->after('rental_contract_id')->constrained('packages')->nullOnDelete();
            }

            if (!Schema::hasColumn('rental_contract_infos', 'payment_type')) {
                $table->string('payment_type')->nullable()->after('package_id');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_type');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'payment_receipt_path')) {
                $table->string('payment_receipt_path')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'payment_confirmed_at')) {
                $table->timestamp('payment_confirmed_at')->nullable()->after('payment_receipt_path');
            }

            if (!Schema::hasColumn('rental_contract_infos', 'payment_confirmed_by')) {
                $table->foreignId('payment_confirmed_by')->nullable()->after('payment_confirmed_at')->constrained('admins')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rental_contract_infos', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contract_infos', 'payment_confirmed_by')) {
                $table->dropConstrainedForeignId('payment_confirmed_by');
            }

            if (Schema::hasColumn('rental_contract_infos', 'payment_confirmed_at')) {
                $table->dropColumn('payment_confirmed_at');
            }

            if (Schema::hasColumn('rental_contract_infos', 'payment_receipt_path')) {
                $table->dropColumn('payment_receipt_path');
            }

            if (Schema::hasColumn('rental_contract_infos', 'payment_status')) {
                $table->dropColumn('payment_status');
            }

            if (Schema::hasColumn('rental_contract_infos', 'payment_type')) {
                $table->dropColumn('payment_type');
            }

            if (Schema::hasColumn('rental_contract_infos', 'package_id')) {
                $table->dropConstrainedForeignId('package_id');
            }
        });
    }
};
