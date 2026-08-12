<?php

namespace Tests\Feature\Admin;

use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Nami\AccessControl\Services\PermissionRegistryService;
use Tests\Concerns\AssertsAgainstBaseline;
use Tests\TestCase;

/**
 * حراسة منظومة الصلاحيات على مستوى المشروع.
 *
 * الفكرة: المسارات هي مصدر الحقيقة. كل مسار إداري إما محمي بصلاحية موجودة فعلاً،
 * أو مسجل في خط الأساس كاستثناء معروف. أي مسار جديد بلا حماية، أو أي صلاحية يطلبها
 * الكود ولا يمكن منحها من الشاشة، يسقط الاختبار في نفس اللحظة التي تُضاف فيها.
 *
 * لتحديث خط الأساس بعد إصلاح متعمد: BASELINE_UPDATE=1 vendor/bin/phpunit
 */
class PermissionCatalogTest extends TestCase
{
    use AssertsAgainstBaseline;
    use DatabaseTransactions;

    /** @test */
    public function no_admin_route_is_left_without_a_permission_gate(): void
    {
        $problems = PermissionCatalog::ungatedRoutes()
            ->map(fn (array $route) => $route['methods'] . ' ' . $route['name'] . ' => ' . $route['uri'])
            ->all();

        $this->assertNoNewProblems(
            'ungated-admin-routes',
            $problems,
            'شاشات إدارية جديدة بلا أي فحص صلاحية'
        );
    }

    /**
     * الضمان الأساسي للنموذج: أمر مزامنة واحد يكفي ليصبح كل ما يطلبه الكود
     * صفاً في جدول الصلاحيات، أي مربع اختيار قابلاً للمنح في /dash/perms.
     * لو أضاف أحد مساراً يطلب صلاحية لا يستطيع المسجل توليدها، يسقط هذا الاختبار.
     *
     * @test
     */
    public function one_sync_registers_every_permission_the_routes_require(): void
    {
        $service = app(PermissionRegistryService::class);

        $definitions = PermissionCatalog::requiredPermissions()
            ->map(fn (string $name) => $service->makeExplicitPermissionDefinition([
                'name' => $name,
                'is_active' => true,
            ]))
            ->all();

        $service->syncPermissionDefinitions($definitions, [], false);

        $this->assertSame(
            [],
            PermissionCatalog::missingPermissions()->all(),
            'بقيت صلاحيات يطلبها الكود ولم يستطع المسجل إنشاءها.'
        );

        $this->assertSame(
            [],
            PermissionCatalog::blockedRoutes()->pluck('name')->all(),
            'بقيت مسارات لا يستطيع أي دور الوصول إليها بعد المزامنة.'
        );
    }

    /** @test */
    public function the_catalog_resolves_permissions_for_package_routes(): void
    {
        $middleware = new \App\Http\Middleware\EnsurePackageRoutePermission();

        $this->assertSame(
            ['finance_bonds_qabd_view', 'finance_module'],
            $middleware->resolvePermissions('finance.bonds-qabd.index', 'finance')
        );

        $this->assertSame(
            ['hr_employees_edit', 'hr_module'],
            $middleware->resolvePermissions('hr.employees.update', 'hr')
        );

        $this->assertSame(
            ['crm_areas_view', 'crm_module'],
            $middleware->resolvePermissions('crm.areas.index', 'crm')
        );

        $this->assertSame(
            ['employee_advances_create', 'employee_module'],
            $middleware->resolvePermissions('employee.advances.store', 'employee')
        );
    }
}
