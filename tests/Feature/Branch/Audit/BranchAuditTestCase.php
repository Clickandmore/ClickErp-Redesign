<?php

namespace Tests\Feature\Branch\Audit;

use App\Models\auth\Admin;
use App\Models\Permission;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Tests\Concerns\InteractsWithBranches;
use Tests\TestCase;

/**
 * اختبارات تقرير مراجعة الفروع (19-10-2026) — docs/testing-reports.
 *
 * كل اختبار يثبت السلوك الصحيح المطلوب لمشكلة واحدة من التقرير، فهو يفشل ما دامت
 * المشكلة مفتوحة وينجح عند إصلاحها. اسم كل اختبار يبدأ برقم المشكلة (C1، A3، ...).
 *
 * المجموعة مستبعدة من التشغيل الافتراضي في phpunit.xml حتى لا تُسقط السويت. للتشغيل:
 *   vendor/bin/phpunit -c phpunit.branch-audit.xml
 * عند إصلاح مشكلة ونجاح اختبارها، انقل الاختبار إلى ملف الفروع العادي المناسب.
 */
abstract class BranchAuditTestCase extends TestCase
{
    use DatabaseTransactions;
    use InteractsWithBranches;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBranches();
        $this->enableBranchesModule();
    }

    /** يمنح الموظف صلاحية مباشرة (لا نعتمد على الأدوار المزروعة في قاعدة الاختبار). */
    protected function grant(Admin $admin, string ...$permissions): Admin
    {
        foreach ($permissions as $permission) {
            Permission::withoutGlobalScopes()->updateOrCreate(
                ['name' => $permission, 'guard_name' => 'admin'],
                [
                    'display_name' => $permission,
                    'group_name' => 'branch_audit',
                    'section_name' => 'branch_audit',
                    'is_active' => true,
                ]
            );

            $admin->givePermissionTo($permission);
        }

        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin->fresh();
    }

    /** موظف مقيّد بفرعه فعلاً، بلا أي صلاحية "كل الفروع". */
    protected function lockedBranchAdmin($branch, string ...$permissions): Admin
    {
        $admin = $this->lockToOwnBranch($this->branchAdmin($branch));

        return $permissions ? $this->grant($admin, ...$permissions) : $admin;
    }
}
