<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_sent_at')) {
                $table->timestamp('employee_sms_sent_at')->nullable()->after('send_error');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_recipients')) {
                $table->json('employee_sms_recipients')->nullable()->after('employee_sms_sent_at');
            }

            if (!Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_error')) {
                $table->text('employee_sms_error')->nullable()->after('employee_sms_recipients');
            }
        });
    }

    public function down(): void
    {
        Schema::table('rental_contract_expiry_reminders', function (Blueprint $table) {
            if (Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_error')) {
                $table->dropColumn('employee_sms_error');
            }

            if (Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_recipients')) {
                $table->dropColumn('employee_sms_recipients');
            }

            if (Schema::hasColumn('rental_contract_expiry_reminders', 'employee_sms_sent_at')) {
                $table->dropColumn('employee_sms_sent_at');
            }
        });
    }
};
