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
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->string('code')->nullable()->after('perished_type');
            $table->date('purchase_date')->nullable()->after('code');
            $table->date('service_start_date')->nullable()->after('purchase_date');
//            $table->foreignId('employee_id')->nullable()->after('service_start_date')->constrained('hr_employee')->onDelete('cascade');
            $table->double('purchase_value')->default(0);
            $table->double('principle_value')->default(0)->after('purchase_value');
            $table->foreignId('creditor_account_id')->nullable()->after('principle_value')->constrained('fn_accounts')->onDelete('cascade');
            $table->string('status')->default('active')->after('creditor_account_id');
            $table->foreignId('account_id')->nullable()->after('status')->constrained('fn_accounts')->onDelete('cascade');
            $table->double('first_tax')->default(0)->after('account_id');
            $table->double('second_tax')->default(0)->after('first_tax');
            $table->string('registration_type')->default('purchase')->after('second_tax');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('fn_principles', function (Blueprint $table) {
            $table->dropColumn('code');
            $table->dropColumn('purchase_date');
            $table->dropColumn('service_start_date');
//            $table->dropForeign(['employee_id']);
//            $table->dropColumn('employee_id');
            $table->dropForeign(['place_id']);
            $table->dropColumn('place_id');
            $table->dropColumn('purchase_value');
            $table->dropColumn('principle_value');
            $table->dropColumn('creditor_account');
            $table->dropForeign(['account_id']);
            $table->dropColumn('account_id');
            $table->dropColumn('first_tax');
            $table->dropColumn('second_tax');
        });
    }
};
