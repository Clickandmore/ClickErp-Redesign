<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Models\Role;
use App\Models\auth\Admin;
use App\PermKeys;
use App\Support\BranchScopedPermissions;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

class AssignPostArrivalRefundPermissionsCommand extends Command
{
    protected $signature = 'permissions:assign-post-arrival-refunds
        {--role= : اسم الدور الإداري الذي ستُمنح له الصلاحيات}
        {--admin= : معرّف مستخدم إداري لمنحه الصلاحيات مباشرة}
        {--dry-run : عرض الهدف والصلاحيات من دون تعديل}';

    protected $description = 'إنشاء ومنح صلاحيات شاشة الاسترداد بعد الوصول لدور إداري أو مستخدم إداري محدد.';

    public function handle(): int
    {
        $names = [
            PermKeys::POST_ARRIVAL_REFUND_VIEW => 'عرض مستندات الاسترداد بعد الوصول',
            PermKeys::POST_ARRIVAL_REFUND_CREATE => 'إنشاء مستند استرداد بعد الوصول',
            PermKeys::POST_ARRIVAL_REFUND_POST => 'اعتماد مستند استرداد بعد الوصول',
            PermKeys::POST_ARRIVAL_REFUND_PAYOUT => 'صرف استرداد بعد الوصول',
            PermKeys::POST_ARRIVAL_REFUND_REVERSE => 'عكس بند استرداد بعد الوصول',
        ];

        $roleName = trim((string) $this->option('role'));
        $adminId = $this->option('admin');
        $role = $roleName !== ''
            ? Role::query()->where('name', $roleName)->where('guard_name', 'admin')->first()
            : null;
        $admin = filled($adminId) ? Admin::query()->find((int) $adminId) : null;

        if ($roleName !== '' && ! $role) {
            $this->error("لم يتم العثور على الدور [{$roleName}] بحارس admin.");
            return self::FAILURE;
        }
        if (filled($adminId) && ! $admin) {
            $this->error("لم يتم العثور على المستخدم الإداري #{$adminId}.");
            return self::FAILURE;
        }
        if (! $role && ! $admin) {
            $this->error('حدد --role أو --admin بهدف صالح.');
            return self::FAILURE;
        }

        $this->table(['الصلاحية', 'الوصف'], collect($names)->map(fn ($label, $name) => [$name, $label])->values()->all());
        $targets = collect([$role ? "الدور: {$role->name}" : null, $admin ? "المستخدم: {$admin->name} (#{$admin->id})" : null])->filter()->implode('، ');
        $this->line('الهدف: ' . $targets);

        $branchTargets = $this->branchTargets($role, $admin);
        if ($branchTargets->isNotEmpty()) {
            $this->line('سيتم تحديث نطاقات الفروع المفعّلة: ' . $branchTargets
                ->map(fn (array $target) => "الدور #{$target['role_id']} / الفرع #{$target['branch_id']}")
                ->implode('، '));
        }

        if ($this->option('dry-run')) {
            $this->warn('معاينة فقط؛ لم يجر أي تعديل.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($names, $role, $admin, $branchTargets): void {
            $columns = Schema::getColumnListing('permissions');
            $permissions = collect($names)->map(function (string $displayName, string $name) use ($columns) {
                $values = ['display_name' => $displayName];
                if (in_array('guard_name', $columns, true)) $values['guard_name'] = 'admin';
                if (in_array('group_name', $columns, true)) $values['group_name'] = 'post_arrival_refund';
                if (in_array('section_name', $columns, true)) $values['section_name'] = 'finance_management';
                if (in_array('is_active', $columns, true)) $values['is_active'] = true;

                return Permission::withoutGlobalScopes()->updateOrCreate(['name' => $name], $values);
            })->values();

            app(PermissionRegistrar::class)->forgetCachedPermissions();
            $role?->givePermissionTo($permissions);
            $admin?->givePermissionTo($permissions);

            foreach ($branchTargets as $target) {
                $existing = BranchScopedPermissions::permissionNamesForRoleBranch(
                    $target['role_id'],
                    $target['branch_id']
                );

                BranchScopedPermissions::syncRoleBranchPermissions(
                    $target['role_id'],
                    $target['branch_id'],
                    $existing->merge(array_keys($names))->unique()->values()
                );
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('تم منح صلاحيات الاسترداد بعد الوصول بنجاح. أعد تحميل الصفحة إن كانت مفتوحة.');

        return self::SUCCESS;
    }

    private function branchTargets(?Role $role, ?Admin $admin)
    {
        if (! Schema::hasTable('role_branch_permission_scopes')) {
            return collect();
        }

        $targets = collect();

        if ($role) {
            $branchIds = DB::table('role_branch_permission_scopes')
                ->where('role_id', $role->id)
                ->pluck('branch_id');

            foreach ($branchIds as $branchId) {
                $targets->push(['role_id' => (int) $role->id, 'branch_id' => (int) $branchId]);
            }
        }

        if ($admin && filled($admin->branch_id)) {
            $roleIds = collect([$admin->role_id]);
            if (method_exists($admin, 'roles')) {
                $roleIds = $roleIds->merge($admin->roles()->pluck('roles.id'));
            }

            foreach ($roleIds->filter()->unique() as $roleId) {
                $hasScope = DB::table('role_branch_permission_scopes')
                    ->where('role_id', $roleId)
                    ->where('branch_id', $admin->branch_id)
                    ->exists();

                if ($hasScope) {
                    $targets->push(['role_id' => (int) $roleId, 'branch_id' => (int) $admin->branch_id]);
                }
            }
        }

        return $targets->unique(fn (array $target) => $target['role_id'] . ':' . $target['branch_id'])->values();
    }
}
