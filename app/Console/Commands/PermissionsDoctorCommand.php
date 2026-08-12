<?php

namespace App\Console\Commands;

use App\Support\PermissionCatalog;
use Illuminate\Console\Command;

class PermissionsDoctorCommand extends Command
{
    protected $signature = 'permissions:doctor
        {--json : إخراج التقرير بصيغة JSON}
        {--details : طباعة القوائم الكاملة وليس الملخص فقط}
        {--module= : حصر النتائج على وحدة واحدة (ingaz, finance, hr, zatca, crm, employee)}
        {--strict : اعتبار الشاشات غير المحمية خطأ أيضاً}';

    protected $description = 'فحص شامل لصحة منظومة الصلاحيات: مسارات محجوبة، صلاحيات ناقصة، صلاحيات ميتة، وشاشات غير محمية.';

    public function handle(): int
    {
        $module = $this->option('module');

        $protectedRoutes = PermissionCatalog::protectedRoutes();
        $ungatedRoutes = PermissionCatalog::ungatedRoutes();
        $blockedRoutes = PermissionCatalog::blockedRoutes();
        $registered = PermissionCatalog::registeredPermissions();
        $grantable = PermissionCatalog::grantablePermissions();
        $unused = PermissionCatalog::unusedPermissions();

        if ($module) {
            $protectedRoutes = $protectedRoutes->where('module', $module)->values();
            $ungatedRoutes = $ungatedRoutes->where('module', $module)->values();
            $blockedRoutes = $blockedRoutes->where('module', $module)->values();

            // احصر أرقام الصلاحيات على الوحدة المطلوبة حتى لا يختلط التقرير بأرقام عامة.
            $prefix = $module === 'ingaz' ? null : $module . '_';
            $scope = fn ($names) => $prefix === null
                ? $names
                : $names->filter(fn (string $name) => str_starts_with($name, $prefix))->values();

            $registered = $scope($registered);
            $grantable = $scope($grantable);
            $unused = ['referenced' => $scope($unused['referenced']), 'orphan' => $scope($unused['orphan'])];
            $required = $protectedRoutes->flatMap(fn (array $route) => $route['permissions'])->unique()->sort()->values();
            $missing = $required->diff(PermissionCatalog::grantablePermissions())->values();
        } else {
            $required = PermissionCatalog::requiredPermissions();
            $missing = PermissionCatalog::missingPermissions();
        }

        $report = [
            'module' => $module ?: 'all',
            'registered_permissions' => $registered->count(),
            'grantable_permissions' => $grantable->count(),
            'disabled_permissions' => $registered->count() - $grantable->count(),
            'required_permissions' => $required->count(),
            'protected_routes' => $protectedRoutes->count(),
            'ungated_routes' => $ungatedRoutes->count(),
            'blocked_routes' => $blockedRoutes->count(),
            'missing_permissions' => $missing->count(),
            'orphan_permissions' => $unused['orphan']->count(),
            'ui_only_permissions' => $unused['referenced']->count(),
            'lists' => [
                'blocked_routes' => $blockedRoutes->all(),
                'missing_permissions' => $missing->all(),
                'orphan_permissions' => $unused['orphan']->all(),
                'ui_only_permissions' => $unused['referenced']->all(),
                'ungated_routes' => $ungatedRoutes->all(),
            ],
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return $this->exitCode($report);
        }

        $this->renderReport($report);

        return $this->exitCode($report);
    }

    private function renderReport(array $report): void
    {
        $this->info('=== فحص منظومة الصلاحيات (' . $report['module'] . ') ===');
        $this->table(['البند', 'العدد', 'الحالة'], [
            ['صفوف الصلاحيات في الجدول', $report['registered_permissions'], '—'],
            ['ظاهرة في الشاشة وقابلة للمنح', $report['grantable_permissions'], '—'],
            ['معطلة (is_active=0)', $report['disabled_permissions'], '—'],
            ['صلاحيات يطلبها النظام فعلياً', $report['required_permissions'], '—'],
            ['مسارات محمية بصلاحية', $report['protected_routes'], '—'],
            ['مسارات محجوبة عن الجميع', $report['blocked_routes'], $this->flag($report['blocked_routes'])],
            ['صلاحيات مطلوبة وغير مسجلة', $report['missing_permissions'], $this->flag($report['missing_permissions'])],
            ['صلاحيات لا يستخدمها شيء (مربعات ميتة)', $report['orphan_permissions'], $this->flag($report['orphan_permissions'], 'تنظيف')],
            ['صلاحيات مستخدمة في الواجهات فقط', $report['ui_only_permissions'], 'مقبول'],
            ['مسارات إدارية بلا أي حماية', $report['ungated_routes'], $this->flag($report['ungated_routes'], 'خطر')],
        ]);

        if (!$this->option('details')) {
            $this->line('استخدم --details لعرض القوائم الكاملة، أو --json للتصدير.');

            return;
        }

        $this->printList('مسارات محجوبة عن الجميع (كل صلاحياتها غير مسجلة)', collect($report['lists']['blocked_routes'])
            ->map(fn (array $route) => $route['name'] . ' => ' . implode(', ', $route['permissions']))->all());
        $this->printList('صلاحيات مطلوبة وغير مسجلة', $report['lists']['missing_permissions']);
        $this->printList('صلاحيات ميتة (لا مسار ولا واجهة تستخدمها)', $report['lists']['orphan_permissions']);
        $this->printList('مسارات إدارية بلا حماية', collect($report['lists']['ungated_routes'])
            ->map(fn (array $route) => $route['methods'] . ' ' . $route['name'])->all());
    }

    private function printList(string $title, array $items): void
    {
        $this->newLine();
        $this->warn($title . ' (' . count($items) . ')');

        foreach ($items as $item) {
            $this->line('  - ' . $item);
        }
    }

    private function flag(int $count, string $label = 'يجب المعالجة'): string
    {
        return $count === 0 ? 'سليم' : $label;
    }

    private function exitCode(array $report): int
    {
        $failed = $report['blocked_routes'] > 0 || $report['missing_permissions'] > 0;

        if ($this->option('strict')) {
            $failed = $failed || $report['ungated_routes'] > 0;
        }

        return $failed ? self::FAILURE : self::SUCCESS;
    }
}
