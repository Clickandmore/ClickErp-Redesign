<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'payment_id')) {
                $table->string('payment_id')->nullable()->after('employee_sms_error');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'payment_url')) {
                $table->string('payment_url', 2048)->nullable()->after('payment_id');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'payment_status')) {
                $table->string('payment_status')->nullable()->after('payment_url');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'extension_id')) {
                $table->unsignedBigInteger('extension_id')->nullable()->after('payment_status');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'contract_effect_type')) {
                $table->string('contract_effect_type')->nullable()->after('extension_id');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'contract_effect_applied_at')) {
                $table->timestamp('contract_effect_applied_at')->nullable()->after('contract_effect_type');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'contract_effect_error')) {
                $table->text('contract_effect_error')->nullable()->after('contract_effect_applied_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            foreach ([
                'contract_effect_error',
                'contract_effect_applied_at',
                'contract_effect_type',
                'extension_id',
                'payment_status',
                'payment_url',
                'payment_id',
            ] as $column) {
                if (Schema::hasColumn('rental_contract_expiry_reminders', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
