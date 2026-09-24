<?php

namespace App\Console\Commands;

use App\Http\Controllers\Permissions\PermissionsController;
use App\Models\Permission;
use App\Support\AdminPermissionNavigation;
use Illuminate\Console\Command;
use ReflectionMethod;

/**
 * فحص عرض شاشة /dash/perms نفسها، وليس صحة الصلاحيات منطقياً:
 *  - هل عنوان كل قسم/مجموعة مترجم أم يظهر بالمفتاح الإنجليزي الخام؟
 *  - هل هناك مجموعة واحدة موزّعة على أكثر من قسم (مربعات مكررة لنفس الشاشة)؟
 *  - هل هناك قسم معرّف في ترتيب الشاشة لكنه فارغ، أو قسم في القاعدة خارج الترتيب؟
 *  - هل ترتيب أقسام الشاشة يطابق ترتيب القائمة الجانبية؟
 *  - هل هناك صلاحيات بلا section_name / group_name / display_name؟
 *
 * الفحص المنطقي (ناقصة/ميتة/مسارات بلا حماية) في: permissions:doctor
 */
class PermissionsScreenAuditCommand extends Command
{
    protected $signature = 'permissions:screen-audit
        {--json : إخراج التقرير بصيغة JSON}
        {--guard=admin : الحارس المراد فحصه}';

    protected $description = 'فحص شاشة الصلاحيات /dash/perms: تطابق المسميات مع القائمة الجانبية، الترتيب، والتكرار.';

    public function handle(): int
    {
        $sections = $this->screenSections();
        $report = [
            'guard' => (string) $this->option('guard'),
            'sections' => count($sections),
            'groups' => array_sum(array_map(fn (array $s) => count($s['groups']), $sections)),
            'permissions' => array_sum(array_map(fn (array $s) => (int) $s['permissions_count'], $sections)),
            'untranslated_sections' => $this->untranslatedSections($sections),
            'untranslated_groups' => $this->untranslatedGroups($sections),
            'duplicated_groups' => $this->duplicatedGroups($sections),
            'empty_ordered_sections' => $this->emptyOrderedSections($sections),
            'unordered_sections' => $this->unorderedSections($sections),
            'sidebar_gaps' => $this->sidebarGaps($sections),
            'order_mismatches' => $this->orderMismatches($sections),
            'unclassified_permissions' => $this->unclassifiedPermissions(),
            'missing_display_names' => $this->missingDisplayNames(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

            return $this->exitCode($report);
        }

        $this->render($report, $sections);

        return $this->exitCode($report);
    }

    /**
     * يبني بنية الشاشة عبر نفس دوال الكنترولر حتى لا يختلف الفحص عن المعروض للمستخدم.
     *
     * @return array<int, array<string, mixed>>
     */
    private function screenSections(): array
    {
        $controller = app(PermissionsController::class);
        $call = function (string $method, ...$args) use ($controller) {
            $reflection = new ReflectionMethod($controller, $method);
            $reflection->setAccessible(true);

            return $reflection->invoke($controller, ...$args);
        };

        $permissions = Permission::query()
            ->where('guard_name', (string) $this->option('guard'))
            ->orderBy('section_name')
            ->orderBy('group_name')
            ->orderBy('name')
            ->get();

        $organized = $call('sortOrganizedPermissions', $call('organizePermissions', $permissions));

        return $call('buildPermissionMatrixSections', $organized);
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array{key: string, permissions: int}>
     */
    private function untranslatedSections(array $sections): array
    {
        return array_values(array_map(
            fn (array $section) => ['key' => $section['key'], 'permissions' => (int) $section['permissions_count']],
            array_filter($sections, fn (array $section) => !AdminPermissionNavigation::hasSection((string) $section['key']))
        ));
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array{section: string, key: string, permissions: int}>
     */
    private function untranslatedGroups(array $sections): array
    {
        $rows = [];

        foreach ($sections as $section) {
            foreach ($section['groups'] as $group) {
                if (!AdminPermissionNavigation::hasGroup((string) $group['key'])) {
                    $rows[] = [
                        'section' => $section['key'],
                        'key' => $group['key'],
                        'permissions' => (int) $group['permissions_count'],
                    ];
                }
            }
        }

        return $rows;
    }

    /**
     * مجموعة واحدة تظهر تحت أكثر من قسم: نفس الشاشة بمربعات موزعة في مكانين.
     *
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array{group: string, places: array<int, string>}>
     */
    private function duplicatedGroups(array $sections): array
    {
        $places = [];

        foreach ($sections as $section) {
            foreach ($section['groups'] as $group) {
                $places[$group['key']][] = $section['key'] . ' (' . $group['permissions_count'] . ')';
            }
        }

        $rows = [];

        foreach ($places as $group => $found) {
            if (count($found) > 1) {
                $rows[] = ['group' => $group, 'places' => $found];
            }
        }

        return $rows;
    }

    /**
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, string>
     */
    private function emptyOrderedSections(array $sections): array
    {
        $present = array_column($sections, 'key');

        return array_values(array_diff($this->declaredSectionOrder(), $present));
    }

    /**
     * أقسام تظهر في الشاشة لكنها خارج قائمة الترتيب، فتسقط في آخر الصفحة بترتيب عشوائي.
     *
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array{key: string, permissions: int}>
     */
    private function unorderedSections(array $sections): array
    {
        $ordered = $this->declaredSectionOrder();

        return array_values(array_map(
            fn (array $section) => ['key' => $section['key'], 'permissions' => (int) $section['permissions_count']],
            array_filter($sections, fn (array $section) => !in_array($section['key'], $ordered, true))
        ));
    }

    /**
     * عناصر القائمة الجانبية التي لا يقابلها قسم مستقل في الشاشة.
     *
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, string>
     */
    private function sidebarGaps(array $sections): array
    {
        $present = array_column($sections, 'key');

        return array_values(array_map(
            fn (string $section) => AdminPermissionNavigation::sectionTitle($section),
            array_diff(AdminPermissionNavigation::sectionOrder(), $present)
        ));
    }

    /**
     * يقارن تسلسل الأقسام المقابلة للقائمة الجانبية بتسلسلها الفعلي في الشاشة.
     *
     * @param array<int, array<string, mixed>> $sections
     * @return array<int, array{section: string, sidebar_position: int, screen_position: int}>
     */
    private function orderMismatches(array $sections): array
    {
        $screenPosition = array_flip(array_column($sections, 'key'));
        $expected = array_values(array_filter(
            AdminPermissionNavigation::sectionOrder(),
            fn (string $section) => isset($screenPosition[$section])
        ));

        $actual = $expected;
        usort($actual, fn (string $first, string $second) => $screenPosition[$first] <=> $screenPosition[$second]);

        $rows = [];

        foreach ($expected as $index => $section) {
            if (($actual[$index] ?? null) !== $section) {
                $rows[] = [
                    'section' => $section,
                    'sidebar_position' => $index + 1,
                    'screen_position' => array_search($section, $actual, true) + 1,
                ];
            }
        }

        return $rows;
    }

    /**
     * @return array<int, string>
     */
    private function unclassifiedPermissions(): array
    {
        return Permission::query()
            ->where('guard_name', (string) $this->option('guard'))
            ->where(fn ($query) => $query->whereNull('section_name')->orWhereNull('group_name')
                ->orWhere('section_name', '')->orWhere('group_name', ''))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * @return array<int, string>
     */
    private function missingDisplayNames(): array
    {
        return Permission::query()
            ->where('guard_name', (string) $this->option('guard'))
            ->where(fn ($query) => $query->whereNull('display_name')->orWhere('display_name', ''))
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    /**
     * ترتيب الأقسام كما يعرّفه sortOrganizedPermissions في الكنترولر.
     *
     * @return array<int, string>
     */
    private function declaredSectionOrder(): array
    {
        return AdminPermissionNavigation::sectionOrder();
    }

    /**
     * @param array<string, mixed> $report
     * @param array<int, array<string, mixed>> $sections
     */
    private function render(array $report, array $sections): void
    {
        $this->info('=== فحص شاشة الصلاحيات /dash/perms (' . $report['guard'] . ') ===');
        $this->table(['البند', 'العدد', 'الحالة'], [
            ['أقسام معروضة', $report['sections'], '—'],
            ['مجموعات معروضة', $report['groups'], '—'],
            ['صلاحيات معروضة', $report['permissions'], '—'],
            ['أقسام بعنوان إنجليزي خام', count($report['untranslated_sections']), $this->flag(count($report['untranslated_sections']))],
            ['مجموعات بعنوان إنجليزي خام', count($report['untranslated_groups']), $this->flag(count($report['untranslated_groups']))],
            ['مجموعات موزعة على أكثر من قسم', count($report['duplicated_groups']), $this->flag(count($report['duplicated_groups']))],
            ['أقسام معرّفة في الترتيب وفارغة', count($report['empty_ordered_sections']), $this->flag(count($report['empty_ordered_sections']))],
            ['أقسام خارج قائمة الترتيب', count($report['unordered_sections']), $this->flag(count($report['unordered_sections']))],
            ['عناصر قائمة جانبية بلا قسم مقابل', count($report['sidebar_gaps']), $this->flag(count($report['sidebar_gaps']))],
            ['أقسام ترتيبها مخالف للقائمة الجانبية', count($report['order_mismatches']), $this->flag(count($report['order_mismatches']))],
            ['صلاحيات بلا قسم/مجموعة', count($report['unclassified_permissions']), $this->flag(count($report['unclassified_permissions']))],
            ['صلاحيات بلا اسم عربي', count($report['missing_display_names']), $this->flag(count($report['missing_display_names']))],
        ]);

        $this->newLine();
        $this->warn('ترتيب الأقسام كما يراه المستخدم:');
        foreach ($sections as $index => $section) {
            $this->line(sprintf(
                '  %2d. %-24s %s (%d صلاحية، %d مجموعة)',
                $index + 1,
                $section['key'],
                $section['title'],
                $section['permissions_count'],
                count($section['groups'])
            ));
        }

        $this->printList('أقسام بعنوان إنجليزي خام', array_map(
            fn (array $row) => $row['key'] . ' — ' . $row['permissions'] . ' صلاحية',
            $report['untranslated_sections']
        ));
        $this->printList('مجموعات بعنوان إنجليزي خام', array_map(
            fn (array $row) => $row['section'] . ' / ' . $row['key'] . ' — ' . $row['permissions'] . ' صلاحية',
            $report['untranslated_groups']
        ));
        $this->printList('مجموعات موزعة على أكثر من قسم', array_map(
            fn (array $row) => $row['group'] . ' => ' . implode(' + ', $row['places']),
            $report['duplicated_groups']
        ));
        $this->printList('أقسام معرّفة في الترتيب لكنها فارغة', $report['empty_ordered_sections']);
        $this->printList('أقسام خارج قائمة الترتيب (تسقط في آخر الصفحة)', array_map(
            fn (array $row) => $row['key'] . ' — ' . $row['permissions'] . ' صلاحية',
            $report['unordered_sections']
        ));
        $this->printList('عناصر في القائمة الجانبية بلا قسم مقابل في الشاشة', $report['sidebar_gaps']);
        $this->printList('أقسام ترتيبها مخالف للقائمة الجانبية', array_map(
            fn (array $row) => $row['section'] . ': القائمة #' . $row['sidebar_position'] . ' مقابل الشاشة #' . $row['screen_position'],
            $report['order_mismatches']
        ));
        $this->printList('صلاحيات بلا قسم/مجموعة (تُصنَّف بالتخمين)', $report['unclassified_permissions']);
        $this->printList('صلاحيات بلا اسم عربي', $report['missing_display_names']);
    }

    /**
     * @param array<int, string> $items
     */
    private function printList(string $title, array $items): void
    {
        if ($items === []) {
            return;
        }

        $this->newLine();
        $this->warn($title . ' (' . count($items) . ')');

        foreach ($items as $item) {
            $this->line('  - ' . $item);
        }
    }

    private function flag(int $count, string $problem = 'مراجعة'): string
    {
        return $count === 0 ? 'سليم' : $problem;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function exitCode(array $report): int
    {
        $blocking = count($report['untranslated_sections'])
            + count($report['untranslated_groups'])
            + count($report['duplicated_groups'])
            + count($report['order_mismatches']);

        return $blocking === 0 ? self::SUCCESS : self::FAILURE;
    }
}
