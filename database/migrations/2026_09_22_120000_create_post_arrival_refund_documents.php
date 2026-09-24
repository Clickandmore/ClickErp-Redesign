<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        if (! Schema::hasTable('return_reason_codes')) {
            Schema::create('return_reason_codes', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->boolean('suggest_client_deduction')->default(false);
                $table->boolean('suggest_office_charge')->default(false);
                $table->string('office_settlement_method')->default('claim');
                $table->boolean('is_active')->default(true)->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->timestamps();
            });

            DB::table('return_reason_codes')->insert([
                ['code' => 'medically_unfit', 'name' => 'غير لائقة طبياً أو حامل', 'suggest_client_deduction' => false, 'suggest_office_charge' => true, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 10, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'refused_or_escaped', 'name' => 'رفض العمل أو هروب داخل الضمان', 'suggest_client_deduction' => true, 'suggest_office_charge' => true, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 20, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'office_error', 'name' => 'خطأ من المكتب الخارجي', 'suggest_client_deduction' => false, 'suggest_office_charge' => true, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 30, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'client_request', 'name' => 'رغبة العميل بلا سبب من العاملة', 'suggest_client_deduction' => true, 'suggest_office_charge' => false, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 40, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'client_problem', 'name' => 'مشكلة مع العميل', 'suggest_client_deduction' => false, 'suggest_office_charge' => false, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 50, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'outside_warranty', 'name' => 'خارج الضمان', 'suggest_client_deduction' => false, 'suggest_office_charge' => false, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 60, 'created_at' => $now, 'updated_at' => $now],
                ['code' => 'other', 'name' => 'سبب آخر', 'suggest_client_deduction' => false, 'suggest_office_charge' => false, 'office_settlement_method' => 'claim', 'is_active' => true, 'sort_order' => 100, 'created_at' => $now, 'updated_at' => $now],
            ]);
        }

        if (! Schema::hasTable('contract_return_settlements')) {
            Schema::create('contract_return_settlements', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('branch_id')->nullable()->index();
                $table->unsignedBigInteger('contract_id')->index();
                $table->unsignedBigInteger('invoice_id')->nullable()->index();
                $table->unsignedBigInteger('return_reason_code_id')->nullable()->index();
                $table->date('settlement_date');
                $table->string('status')->default('draft')->index();
                $table->text('reason');
                $table->text('notes')->nullable();
                $table->decimal('proposed_total', 14, 2)->default(0);
                $table->decimal('approved_total', 14, 2)->default(0);
                $table->decimal('client_due_amount', 14, 2)->default(0);
                $table->decimal('client_paid_amount', 14, 2)->default(0);
                $table->json('settings_snapshot')->nullable();
                $table->unsignedBigInteger('created_by_id')->nullable();
                $table->unsignedBigInteger('approved_by_id')->nullable();
                $table->timestamp('approved_at')->nullable();
                $table->timestamps();
                $table->softDeletes();
                $table->index(['contract_id', 'status'], 'return_settlement_contract_status_idx');
            });
        }

        if (! Schema::hasTable('contract_return_settlement_lines')) {
            Schema::create('contract_return_settlement_lines', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('settlement_id')->index();
                $table->string('type')->index();
                $table->string('status')->default('draft')->index();
                $table->decimal('proposed_amount', 14, 2)->default(0);
                $table->decimal('approved_amount', 14, 2)->default(0);
                $table->unsignedBigInteger('debit_account_id')->nullable();
                $table->unsignedBigInteger('credit_account_id')->nullable();
                $table->string('settlement_method')->nullable();
                $table->text('reason')->nullable();
                $table->unsignedBigInteger('constraint_id')->nullable()->index();
                $table->unsignedBigInteger('bond_id')->nullable()->index();
                $table->unsignedBigInteger('credit_note_id')->nullable()->index();
                $table->unsignedBigInteger('financial_request_id')->nullable()->index();
                $table->unsignedBigInteger('reversal_constraint_id')->nullable()->index();
                $table->unsignedBigInteger('reversed_by_id')->nullable();
                $table->timestamp('reversed_at')->nullable();
                $table->text('reversal_reason')->nullable();
                $table->json('account_snapshot')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->foreign('settlement_id', 'return_settlement_lines_settlement_fk')->references('id')->on('contract_return_settlements')->cascadeOnDelete();
            });
        }

        if (Schema::hasTable('financial_requests') && ! Schema::hasColumn('financial_requests', 'return_settlement_id')) {
            Schema::table('financial_requests', function (Blueprint $table) {
                $table->unsignedBigInteger('return_settlement_id')->nullable()->after('contract_id')->index();
            });
        }

        if (Schema::hasTable('permissions')) {
            $columns = Schema::getColumnListing('permissions');
            foreach ([
                'post_arrival_refund_view' => 'عرض مستندات الاسترداد بعد الوصول',
                'post_arrival_refund_create' => 'إنشاء مستند استرداد بعد الوصول',
                'post_arrival_refund_post' => 'اعتماد مستند استرداد بعد الوصول',
                'post_arrival_refund_payout' => 'صرف استرداد بعد الوصول',
                'post_arrival_refund_reverse' => 'عكس بند استرداد بعد الوصول',
            ] as $name => $displayName) {
                $payload = ['name' => $name, 'display_name' => $displayName, 'created_at' => $now, 'updated_at' => $now];
                if (in_array('guard_name', $columns, true)) $payload['guard_name'] = 'admin';
                if (in_array('group_name', $columns, true)) $payload['group_name'] = 'post_arrival_refund';
                if (in_array('section_name', $columns, true)) $payload['section_name'] = 'finance_management';
                if (in_array('is_active', $columns, true)) $payload['is_active'] = true;
                DB::table('permissions')->updateOrInsert(['name' => $name], $payload);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('financial_requests') && Schema::hasColumn('financial_requests', 'return_settlement_id')) {
            Schema::table('financial_requests', fn (Blueprint $table) => $table->dropColumn('return_settlement_id'));
        }
        Schema::dropIfExists('contract_return_settlement_lines');
        Schema::dropIfExists('contract_return_settlements');
        Schema::dropIfExists('return_reason_codes');
    }
};
