<?php

namespace Tests\Feature\Admin;

use App\Http\Controllers\admin\ActivityLogController;
use App\PermKeys;
use App\Support\AdminRoutePermissionMatrix;
use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;
use Tests\TestCase;

/**
 * تصدير سجل النشاط يجب أن يطبق نفس فلاتر الشاشة، وأن يكون محمياً بصلاحية التصدير.
 */
class ActivityLogExportTest extends TestCase
{
    use DatabaseTransactions;

    /** @test */
    public function export_route_is_gated_by_the_activity_export_permission(): void
    {
        $route = app('router')->getRoutes()->getByName('dash.activity.log.export');

        $this->assertNotNull($route, 'مسار تصدير سجل النشاط غير مسجل.');
        $this->assertContains('admin.route.permission', $route->gatherMiddleware());
        $this->assertSame(
            [PermKeys::ACTIVITY_EXPORT],
            AdminRoutePermissionMatrix::resolve('dash.activity.log.export')
        );
    }

    /** @test */
    public function pdf_export_route_is_gated_by_the_activity_export_permission(): void
    {
        $route = app('router')->getRoutes()->getByName('dash.activity.log.export-pdf');

        $this->assertNotNull($route, 'مسار تصدير PDF لسجل النشاط غير مسجل.');
        $this->assertContains('admin.route.permission', $route->gatherMiddleware());
        $this->assertSame(
            [PermKeys::ACTIVITY_EXPORT],
            AdminRoutePermissionMatrix::resolve('dash.activity.log.export-pdf')
        );
    }

    /** @test */
    public function pdf_export_returns_a_pdf_for_the_filtered_rows(): void
    {
        $logName = 'export_pdf_test_' . uniqid();
        $this->makeActivity($logName, 'created', 'PDF-MATCH', '2026-01-10 09:00:00');

        $response = app(ActivityLogController::class)->exportPdf(Request::create('/dash/activity-log/export-pdf', 'GET', [
            'log_name' => $logName,
        ]));

        $this->assertSame(200, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    /** @test */
    public function export_only_contains_rows_matching_the_filters(): void
    {
        $logName = 'export_test_' . uniqid();

        $this->makeActivity($logName, 'created', 'MATCH-CREATED', '2026-01-10 09:00:00');
        $this->makeActivity($logName, 'updated', 'SKIP-UPDATED', '2026-01-10 10:00:00');
        $this->makeActivity($logName, 'created', 'SKIP-OUT-OF-RANGE', '2026-02-01 09:00:00');
        $this->makeActivity('other_' . uniqid(), 'created', 'SKIP-OTHER-LOG', '2026-01-10 09:00:00');

        $csv = $this->runExport([
            'log_name' => $logName,
            'action' => 'created',
            'date_from' => '2026-01-01',
            'date_to' => '2026-01-31',
        ]);

        $this->assertStringStartsWith("\xEF\xBB\xBF", $csv, 'الملف يحتاج BOM ليقرأ Excel العربية بشكل صحيح.');
        $this->assertStringContainsString('رقم السجل', $csv);
        $this->assertStringContainsString('MATCH-CREATED', $csv);
        $this->assertStringNotContainsString('SKIP-UPDATED', $csv);
        $this->assertStringNotContainsString('SKIP-OUT-OF-RANGE', $csv);
        $this->assertStringNotContainsString('SKIP-OTHER-LOG', $csv);
    }

    private function makeActivity(string $logName, string $event, string $marker, string $createdAt): Activity
    {
        $activity = new Activity();
        $activity->log_name = $logName;
        $activity->description = $event;
        $activity->event = $event;
        $activity->properties = collect(['attributes' => ['name' => $marker]]);
        $activity->created_at = $createdAt;
        $activity->updated_at = $createdAt;
        $activity->save();

        return $activity;
    }

    private function runExport(array $filters): string
    {
        $response = app(ActivityLogController::class)->export(Request::create('/dash/activity-log/export', 'GET', $filters));

        ob_start();
        $response->sendContent();

        return (string) ob_get_clean();
    }
}
