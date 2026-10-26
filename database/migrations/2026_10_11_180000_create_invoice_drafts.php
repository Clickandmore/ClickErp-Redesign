<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('invoice_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('created_by');
            $table->string('status')->default('pending')->index();
            $table->string('reference_key')->nullable()->unique();
            $table->json('payload');
            $table->unsignedBigInteger('invoice_id')->nullable()->unique();
            $table->unsignedBigInteger('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamps();
        });
        foreach ([PermKeys::INVOICES_CREATE_DRAFT => 'إنشاء مسودة فاتورة', PermKeys::INVOICES_DRAFTS_VIEW => 'عرض طلبات اعتماد الفواتير', PermKeys::INVOICES_APPROVE => 'اعتماد وإرجاع مسودات الفواتير'] as $name => $label) {
            $data = ['created_at' => now(), 'updated_at' => now()];
            foreach (['display_name' => $label, 'description' => $label, 'group_name' => 'invoices', 'section_name' => 'recruitment_contracts', 'is_active' => true] as $column => $value) {
                if (Schema::hasColumn('permissions', $column)) $data[$column] = $value;
            }
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $data);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_drafts');
        $ids = DB::table('permissions')->where('guard_name', 'admin')
            ->whereIn('name', [PermKeys::INVOICES_CREATE_DRAFT, PermKeys::INVOICES_DRAFTS_VIEW, PermKeys::INVOICES_APPROVE])->pluck('id');
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) DB::table($table)->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
