<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('contract_escape_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('contract_id')->unique()->constrained('contracts')->cascadeOnDelete();
            $table->date('escape_date');
            $table->string('contract_status_at_escape')->default('داخل الضمان');
            $table->text('notes')->nullable();
            $table->json('attachments');
            $table->string('process_status')->default('escape_reported');
            $table->foreignId('reported_by')->nullable()->constrained('admins')->nullOnDelete();
            $table->timestamps();
        });
        foreach ([
            'rec_contracts_escape_reports_view' => 'عرض بلاغات الهروب',
            'rec_contracts_escape_report_create' => 'إضافة بلاغ هروب',
            'rec_contracts_escape_report_delete' => 'حذف بلاغ هروب',
        ] as $name => $label) {
            \App\Models\Permission::updateOrCreate(['name' => $name, 'guard_name' => 'admin'], [
                'display_name' => $label, 'group_name' => 'recruitment_contracts',
                'section_name' => 'operations_management', 'is_active' => true,
            ]);
        }
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('contract_escape_reports');
    }
};
