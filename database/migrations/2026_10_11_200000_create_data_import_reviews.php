<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('data_import_batches', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('path');
            $table->string('original_name');
            $table->string('file_hash', 64)->index();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->unsignedBigInteger('branch_id')->nullable()->index();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('options')->nullable();
            $table->json('result')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
        });
        Schema::create('data_import_rows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('batch_id')->constrained('data_import_batches')->cascadeOnDelete();
            $table->string('source_row');
            $table->string('fingerprint', 64)->index();
            $table->string('status')->default('valid')->index();
            $table->json('payload');
            $table->json('issues');
            $table->timestamps();
        });
        // One lock per import type serializes duplicate rechecks and commits.
        Schema::create('data_import_locks', function (Blueprint $table) {
            $table->string('type')->primary();
        });
        foreach (['data_import_upload' => 'رفع وفحص بيانات الاستيراد',
            'data_import_review' => 'عرض ومراجعة ملفات الاستيراد',
            'data_import_approve' => 'اعتماد استيراد البيانات',
            'data_import_generate_bonds' => 'توليد السندات من القيود المستوردة'] as $name => $label) {
            $data = ['created_at' => now(), 'updated_at' => now()];
            foreach (['display_name' => $label, 'description' => $label,
                'group_name' => 'data_import', 'section_name' => 'reports', 'is_active' => true] as $column => $value) {
                if (Schema::hasColumn('permissions', $column)) $data[$column] = $value;
            }
            DB::table('permissions')->updateOrInsert(['name' => $name, 'guard_name' => 'admin'], $data);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('data_import_rows');
        Schema::dropIfExists('data_import_batches');
        Schema::dropIfExists('data_import_locks');
        $ids = DB::table('permissions')->where('guard_name', 'admin')->whereIn('name', [
            'data_import_upload', 'data_import_review', 'data_import_approve', 'data_import_generate_bonds',
        ])->pluck('id');
        foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
            if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) DB::table($table)->whereIn('permission_id', $ids)->delete();
        }
        DB::table('permissions')->whereIn('id', $ids)->delete();
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
