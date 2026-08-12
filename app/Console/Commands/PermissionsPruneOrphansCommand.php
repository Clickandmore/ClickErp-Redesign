<?php

namespace App\Console\Commands;

use App\Models\Permission;
use App\Support\PermissionCatalog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Spatie\Permission\PermissionRegistrar;

/**
 * تعطيل الصلاحيات اليتيمة: صفوف موجودة في شاشة /dash/perms لكن لا يطلبها أي مسار
 * ولا تُذكر في أي واجهة، أي مربعات اختيار لا تحمي شيئا وتشوش المراجعة.
 *
 * التعطيل يتم عبر is_active=0 وليس الحذف، ويُحفظ ملف استرجاع في storage
 * لأن موديل Permission يطبق global scope على is_active، فالصف المعطل يختفي
 * عن كل الاستعلامات ولا يمكن استرجاعه من الشاشة نفسها.
 */
class PermissionsPruneOrphansCommand extends Command
{
    protected $signature = 'permissions:prune-orphans
        {--apply : تنفيذ التعطيل فعليا، بدونه يعرض الأمر النتيجة فقط}
        {--include-earmarked : تعطيل الصلاحيات المحجوزة لشاشات غير محمية بعد أيضا}
        {--restore= : إعادة تفعيل الأسماء الموجودة في ملف استرجاع سابق}';

    protected $description = 'تعطيل الصلاحيات التي لا يستخدمها أي مسار ولا أي واجهة، مع ملف استرجاع.';

    private const ACTION_SUFFIXES = [
        'view', 'show', 'read', 'create', 'store', 'edit', 'update', 'delete', 'destroy',
        'control', 'manage', 'assign', 'approve', 'reject', 'export', 'import', 'print',
        'analytics', 'reports', 'convert', 'download', 'upload', 'pin', 'unpin',
    ];

    /** @var array<string, true>|null */
    private ?array $earmarkedResources = null;

    public function handle(): int
    {
        if ($restorePath = $this->option('restore')) {
            return $this->restore($restorePath);
        }

        $orphans = PermissionCatalog::unusedPermissions()['orphan'];
        $earmarked = $orphans->filter(fn (string $name) => $this->isEarmarked($name))->values();
        $targets = $this->option('include-earmarked')
            ? $orphans
            : $orphans->reject(fn (string $name) => $this->isEarmarked($name))->values();

        $this->info('الصلاحيات اليتيمة: ' . $orphans->count());

        if ($earmarked->isNotEmpty()) {
            $this->line('منها محجوزة لشاشات إدارية غير محمية بعد: ' . $earmarked->count()
                . ($this->option('include-earmarked') ? ' (مشمولة بالتعطيل)' : ' (مستثناة، استخدم --include-earmarked لتشملها)'));
        }

        $this->line('المستهدف للتعطيل: ' . $targets->count());

        if ($targets->isEmpty()) {
            $this->info('لا شيء للتعطيل.');

            return self::SUCCESS;
        }

        if (!$this->option('apply')) {
            foreach ($targets as $name) {
                $this->line('  - ' . $name);
            }

            $this->newLine();
            $this->warn('عرض فقط. أضف --apply للتنفيذ.');

            return self::SUCCESS;
        }

        $restoreFile = storage_path('app/permissions-pruned-' . now()->format('Ymd-His') . '.json');
        File::ensureDirectoryExists(dirname($restoreFile));
        File::put($restoreFile, $targets->toJson(JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $affected = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->whereIn('name', $targets)
            ->update(['is_active' => false]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('تم تعطيل ' . $affected . ' صلاحية.');
        $this->line('ملف الاسترجاع: ' . $restoreFile);
        $this->warn('ملاحظة: أول حفظ لأي دور من /dash/perms بعد التعطيل يزيل هذه الصلاحيات من '
            . 'role_has_permissions نهائيا، لأن الاستعلام المستخدم في الحفظ يستبعد المعطل. '
            . 'استرجعها قبل ذلك إن احتجتها.');

        return self::SUCCESS;
    }

    private function restore(string $path): int
    {
        if (!File::exists($path)) {
            $this->error('ملف الاسترجاع غير موجود: ' . $path);

            return self::FAILURE;
        }

        $names = collect(json_decode(File::get($path), true) ?: []);

        if ($names->isEmpty()) {
            $this->error('ملف الاسترجاع فارغ أو غير صالح.');

            return self::FAILURE;
        }

        $affected = Permission::withoutGlobalScopes()
            ->where('guard_name', 'admin')
            ->whereIn('name', $names)
            ->update(['is_active' => true]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->info('تمت إعادة تفعيل ' . $affected . ' صلاحية من ' . $names->count() . ' اسم.');

        return self::SUCCESS;
    }

    /**
     * الصلاحية "محجوزة" إذا كان اسمها يطابق مورد شاشة إدارية غير محمية حاليا.
     * هي تبدو يتيمة فقط لأن الشاشة لا تفحص شيئا بعد، وتعطيلها اليوم يعني
     * أن أول محاولة لحماية تلك الشاشة ستقفلها على كل الأدوار.
     */
    private function isEarmarked(string $name): bool
    {
        $resource = $this->resourceOf($name);

        if ($resource === '') {
            return false;
        }

        foreach (array_keys($this->earmarkedResources()) as $routeResource) {
            if (
                $routeResource === $resource
                || str_ends_with($routeResource, '_' . $resource)
                || str_starts_with($resource, $routeResource . '_')
            ) {
                return true;
            }
        }

        return false;
    }

    /**
     * موارد كل مسار إداري غير محمي، بصيغة اسم الصلاحية (بدون فعل النهاية).
     *
     * @return array<string, true>
     */
    private function earmarkedResources(): array
    {
        if ($this->earmarkedResources !== null) {
            return $this->earmarkedResources;
        }

        $resources = [];

        foreach (PermissionCatalog::ungatedRoutes() as $route) {
            $segments = explode('.', (string) $route['name']);
            array_pop($segments);

            if ($segments === []) {
                continue;
            }

            $resources[$this->normalize(implode('_', $segments))] = true;

            // بادئة الوحدة نفسها: أي وحدة فيها شاشات غير محمية تبقى صلاحياتها كلها محجوزة،
            // لأن حمايتها لاحقا ستطلبها باصطلاح التسمية حتى لو اختلف اسم المسار عن اسم الصلاحية.
            $resources[$this->normalize($segments[0])] = true;
        }

        return $this->earmarkedResources = $resources;
    }

    private function resourceOf(string $permissionName): string
    {
        $normalized = $this->normalize($permissionName);

        foreach (self::ACTION_SUFFIXES as $suffix) {
            if (str_ends_with($normalized, '_' . $suffix)) {
                return substr($normalized, 0, -strlen($suffix) - 1);
            }
        }

        return $normalized;
    }

    private function normalize(string $value): string
    {
        return trim(str_replace(['-', '.'], '_', $value), '_');
    }
}
