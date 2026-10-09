<?php

use App\PermKeys;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('nationalities') && !Schema::hasColumn('nationalities', 'musaned_commission_rate')) {
            Schema::table('nationalities', function (Blueprint $table) {
                $table->decimal('musaned_commission_rate', 8, 4)
                    ->default(2.4)
                    ->after('government_cost');
            });
        }

        if (!Schema::hasTable('permissions')) {
            return;
        }

        $now = now();
        $permissions = [
            PermKeys::INVOICES_EDIT_LINE_ITEMS => [
                'display_name' => 'تعديل بنود وقيم الفاتورة',
                'description' => 'السماح بتغيير قيم البنود والكميات والخصم عن القيم الافتراضية للجنسية.',
            ],
            PermKeys::INVOICES_EDIT_MUSANED_COMMISSION => [
                'display_name' => 'تعديل مبلغ التكاليف البنكية',
                'description' => 'السماح بتعديل مبلغ التكاليف البنكية المحسوب من نسبة الجنسية عند إصدار الفاتورة.',
            ],
        ];

        foreach ($permissions as $name => $metadata) {
            $attributes = [
                'guard_name' => 'admin',
                'created_at' => $now,
                'updated_at' => $now,
            ];

            foreach ([
                'group_name' => 'invoices',
                'section_name' => 'recruitment_contracts',
                'display_name' => $metadata['display_name'],
                'description' => $metadata['description'],
                'is_active' => true,
            ] as $column => $value) {
                if (Schema::hasColumn('permissions', $column)) {
                    $attributes[$column] = $value;
                }
            }

            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'admin'],
                $attributes
            );
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        if (Schema::hasTable('nationalities') && Schema::hasColumn('nationalities', 'musaned_commission_rate')) {
            Schema::table('nationalities', function (Blueprint $table) {
                $table->dropColumn('musaned_commission_rate');
            });
        }

        if (Schema::hasTable('permissions')) {
            $ids = DB::table('permissions')
                ->where('guard_name', 'admin')
                ->whereIn('name', [PermKeys::INVOICES_EDIT_LINE_ITEMS, PermKeys::INVOICES_EDIT_MUSANED_COMMISSION])
                ->pluck('id');

            foreach (['role_has_permissions', 'model_has_permissions', 'role_branch_permissions'] as $table) {
                if (Schema::hasTable($table) && Schema::hasColumn($table, 'permission_id')) {
                    DB::table($table)->whereIn('permission_id', $ids)->delete();
                }
            }

            DB::table('permissions')->whereIn('id', $ids)->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
