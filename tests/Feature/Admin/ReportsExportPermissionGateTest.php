<?php

namespace Tests\Feature\Admin;

use App\Services\ingaz\ReportExportService;
use App\Support\AdminRoutePermissionMatrix;
use App\Support\PermissionCatalog;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Nami\AccessControl\Services\PermissionRegistryService;
use Tests\TestCase;

/**
 * حراسة تصدير التقارير.
 *
 * العطل الذي يمنعه هذا الاختبار: بوابة المسار reports.export كانت تطلب
 * reports_export / reports_manage فقط، بينما ReportExportService تسمح بالتصدير
 * لمن يملك صلاحية التصدير الخاصة بالقسم (maids_export، orders_export ...).
 * النتيجة أن الشاشة تعرض التقرير وزر التصدير، ثم يرد الميدلوير 403 قبل الوصول
 * إلى الخدمة أصلاً. أي انفصال جديد بين المصدرين يسقط هذا الاختبار فوراً.
 */
class ReportsExportPermissionGateTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function route_gate_accepts_every_permission_the_report_service_accepts(): void
    {
        $gate = AdminRoutePermissionMatrix::resolve('reports.export', Request::create('/admin/reports/export/maids'));

        $this->assertNotEmpty($gate, 'مسار reports.export بلا بوابة صلاحيات.');

        foreach (ReportExportService::permissionMap() as $reportKey => $formats) {
            foreach ($formats as $format => $permissions) {
                $this->assertNotEmpty(
                    $permissions,
                    "التقرير [{$reportKey}] بصيغة [{$format}] بلا صلاحيات، فلن يتمكن أحد من تصديره."
                );

                foreach ($permissions as $permission) {
                    $this->assertContains(
                        $permission,
                        $gate,
                        "الصلاحية [{$permission}] تسمح بتصدير [{$reportKey}/{$format}] داخل الخدمة "
                        . 'لكن بوابة المسار reports.export ترفضها، فيظهر الزر ويرد 403.'
                    );
                }
            }
        }
    }

    /** @test */
    public function reports_index_gate_matches_the_export_gate(): void
    {
        $this->assertSame(
            AdminRoutePermissionMatrix::resolve('reports.export', Request::create('/admin/reports/export/maids')),
            AdminRoutePermissionMatrix::resolve('reports.index', Request::create('/admin/reports')),
            'شاشة التقارير وبوابة التصدير يجب أن تطلبا نفس الصلاحيات، وإلا ظهرت شاشة بلا تقرير واحد قابل للتصدير.'
        );
    }

    /** @test */
    public function every_report_export_permission_is_grantable_after_one_sync(): void
    {
        $service = app(PermissionRegistryService::class);

        $definitions = collect(PermissionCatalog::requiredPermissions())
            ->map(fn (string $name) => $service->makeExplicitPermissionDefinition([
                'name' => $name,
                'is_active' => true,
            ]))
            ->all();

        $service->syncPermissionDefinitions($definitions, [], false);

        $grantable = PermissionCatalog::grantablePermissions('admin');

        $ungrantable = collect(ReportExportService::allExportPermissions())
            ->reject(fn (string $permission) => $grantable->contains($permission))
            ->values()
            ->all();

        $this->assertSame(
            [],
            $ungrantable,
            'صلاحيات تصدير لا يمكن منحها من /dash/perms بعد المزامنة: ' . implode(', ', $ungrantable)
        );
    }
}
