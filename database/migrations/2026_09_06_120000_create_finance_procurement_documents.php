<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fn_procurement_documents', function (Blueprint $t) {
            $t->id();
            $t->string('kind', 20);
            $t->string('status', 20)->default('draft');
            $t->unsignedBigInteger('branch_id')->nullable()->index();
            $t->unsignedBigInteger('supplier_id');
            $t->string('supplier_reference')->nullable();
            $t->date('date');
            $t->date('due_date')->nullable();
            $t->text('notes')->nullable();
            $t->unsignedBigInteger('contract_id')->nullable();
            $t->unsignedBigInteger('cost_center_id')->nullable();
            $t->unsignedBigInteger('tax_account_id')->nullable();
            $t->unsignedBigInteger('source_id')->nullable()->unique();
            $t->unsignedBigInteger('constraint_id')->nullable()->unique();
            $t->unsignedBigInteger('supplier_account_id')->nullable();
            $t->decimal('subtotal', 16, 2);
            $t->decimal('discount', 16, 2);
            $t->decimal('tax', 16, 2);
            $t->decimal('total', 16, 2);
            $t->unsignedBigInteger('created_by_id');
            $t->unsignedBigInteger('approved_by_id')->nullable();
            $t->timestamp('approved_at')->nullable();
            $t->timestamps();
            $t->index(['kind', 'status', 'date']);
        });
        Schema::create('fn_procurement_lines', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('fn_procurement_documents')->cascadeOnDelete();
            $t->string('description', 500);
            $t->unsignedBigInteger('account_id');
            $t->decimal('quantity', 12, 3);
            $t->decimal('unit_price', 14, 2);
            $t->decimal('discount', 14, 2);
            $t->decimal('tax_rate', 5, 2);
            $t->decimal('net', 16, 2);
            $t->decimal('tax', 16, 2);
            $t->decimal('total', 16, 2);
            $t->timestamps();
        });
        Schema::create('fn_procurement_payments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('document_id')->constrained('fn_procurement_documents');
            $t->decimal('amount', 16, 2);
            $t->date('date');
            $t->unsignedBigInteger('account_id');
            $t->unsignedBigInteger('constraint_id')->unique();
            $t->string('reference')->nullable();
            $t->uuid('token')->unique();
            $t->unsignedBigInteger('created_by_id');
            $t->timestamps();
        });
        foreach (['view' => 'عرض المشتريات', 'create' => 'إضافة المشتريات', 'edit' => 'تعديل المشتريات', 'approve' => 'اعتماد المشتريات', 'pay' => 'سداد المشتريات'] as $action => $label) {
            DB::table('permissions')->updateOrInsert(['name' => 'finance_procurement_' . $action, 'guard_name' => 'admin'], ['display_name' => $label, 'group_name' => 'finance_procurement', 'section_name' => 'finance', 'is_active' => true]);
            $permission = DB::table('permissions')->where('name', 'finance_procurement_' . $action)->where('guard_name', 'admin')->value('id');
            foreach (DB::table('roles')->where('name', 'Administrator')->where('guard_name', 'admin')->pluck('id') as $role) {
                DB::table('role_has_permissions')->insertOrIgnore(['role_id' => $role, 'permission_id' => $permission]);
            }
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
    public function down(): void
    {
        Schema::dropIfExists('fn_procurement_payments');
        Schema::dropIfExists('fn_procurement_lines');
        Schema::dropIfExists('fn_procurement_documents');
    }
};
