<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillAdminsBranchIdToMainBranch extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admins') || ! Schema::hasColumn('admins', 'branch_id')) {
            return;
        }

        if (! Schema::hasTable('branches') || ! Schema::hasColumn('branches', 'is_main')) {
            return;
        }

        // الفرع الرئيسي — أولوية لـ is_main = true، وإلا أقدم فرع نشط
        $mainBranch = DB::table('branches')
            ->where('is_active', true)
            ->orderByDesc('is_main')
            ->orderBy('id')
            ->first(['id']);

        if (! $mainBranch) {
            return;
        }

        // وكلاء المكاتب الخارجية — يُستثنون من ربط الفرع
        $agentAdminIds = Schema::hasTable('admin_offices')
            ? DB::table('admin_offices')->distinct()->pluck('admin_id')
            : collect();

        // ربط الـ admins الحقيقيين فقط (غير المحذوفين، غير المربوطين بفرع، غير الوكلاء)
        DB::table('admins')
            ->whereNull('branch_id')
            ->whereNull('deleted_at')
            ->when($agentAdminIds->isNotEmpty(), fn ($q) => $q->whereNotIn('id', $agentAdminIds))
            ->update(['branch_id' => $mainBranch->id]);
    }

    public function down(): void
    {
        // لا يمكن التراجع بأمان — branch_id يبقى كما هو
    }
}
