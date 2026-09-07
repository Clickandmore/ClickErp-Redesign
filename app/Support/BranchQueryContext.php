<?php

namespace App\Support;

use App\NewPermKeys;
use App\Services\BranchAccessService;
use Illuminate\Support\Facades\Auth;

/**
 * يقرر متى يُطبَّق عزل الفروع تلقائياً على استعلامات الموديلات.
 *
 * العزل التلقائي مخصص لطلبات لوحة التحكم فقط:
 *  - موديول branches مفعّل، و
 *  - الطلب يعمل تحت حارس admin، و
 *  - الموظف مرتبط بفرع ولا يملك صلاحية branches_view_all.
 *
 * يبقى بلا عزل: أوامر artisan والكرون والمهام (تعمل على مستوى الشركة)،
 * وحراس web/api/agent (مسار العميل والوكيل له فلترته الخاصة)،
 * والإدارة العامة (admin بلا branch_id).
 *
 * الاستثناء الصريح عبر suspended() لتقارير تحتاج أرقاماً تجميعية.
 */
class BranchQueryContext
{
    private static int $suspensions = 0;

    /**
     * ذاكرة مؤقتة داخل الطلب الواحد — الـ scope يعمل على كل استعلام.
     * المفتاح هو هوية المستخدم الحالي، فتغيّرها يُبطل الذاكرة تلقائياً
     * (بين طلبين، وبين اختبارين في نفس العملية).
     */
    private static ?int $resolvedBranchId = null;
    private static ?string $resolvedKey = null;

    /** تشغيل كتلة بدون عزل فرع (تقارير تجميعية، مهام إدارية). */
    public static function withoutBranchScope(callable $callback)
    {
        self::$suspensions++;

        try {
            return $callback();
        } finally {
            self::$suspensions--;
        }
    }

    public static function suspended(): bool
    {
        return self::$suspensions > 0;
    }

    /** الفرع الذي يجب أن تُقيَّد به الاستعلامات الآن، أو null إذا لا عزل. */
    public static function activeBranchId(): ?int
    {
        if (self::$suspensions > 0) {
            return null;
        }

        $admin = self::currentAdmin();
        $key = $admin ? 'admin:' . $admin->getAuthIdentifier() : 'guest';

        if (self::$resolvedKey === $key) {
            return self::$resolvedBranchId;
        }

        self::$resolvedKey = $key;
        self::$resolvedBranchId = self::resolve($admin);

        return self::$resolvedBranchId;
    }

    /** تُستدعى عند تغيّر المستخدم الحالي (تسجيل دخول/خروج، وبين الاختبارات). */
    public static function flush(): void
    {
        self::$resolvedKey = null;
        self::$resolvedBranchId = null;
    }

    /**
     * الموظف الحالي، أو null إذا لم يوجد.
     *
     * الـ scope يمرّ من هنا على كل استعلام، بما في ذلك استعلامات تعمل بينما
     * يكون حارس admin مستبدلاً بـ mock في الاختبارات. أي عطل في فحص الحارس
     * يُعامل كـ"لا مستخدم" — وهو سلوك النظام قبل إدخال العزل أصلاً.
     */
    private static function currentAdmin()
    {
        try {
            $guard = Auth::guard('admin');

            if (! is_object($guard) || ! method_exists($guard, 'check') || ! method_exists($guard, 'user')) {
                return null;
            }

            return $guard->check() ? $guard->user() : null;
        } catch (\Throwable) {
            return null;
        }
    }

    public static function reset(): void
    {
        self::$suspensions = 0;
        self::flush();
    }

    private static function resolve($admin): ?int
    {
        // أوامر artisan والكرون تعمل على مستوى الشركة. لا يوجد فيها مستخدم
        // مسجّل دخوله أصلاً، لكن الاستثناء صريح هنا حتى لا يعتمد الأمان على ذلك.
        // اختبارات PHPUnit تعمل في الـ console أيضاً، فتُستثنى من هذا الاستثناء.
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        if (! SystemModuleManager::isEnabled('branches')) {
            return null;
        }

        if (! $admin) {
            return null;
        }

        $branchId = (int) ($admin->branch_id ?? 0);

        if ($branchId <= 0) {
            return null;
        }

        if (AdminRoutePermissionMatrix::adminHasAnyPermission($admin, [NewPermKeys::BRANCHES_VIEW_ALL])) {
            return null;
        }

        return $branchId;
    }

    /** هل الفرع النشط هو الفرع الرئيسي؟ (يرى السجلات القديمة بلا فرع). */
    public static function activeBranchIsMain(): bool
    {
        $branchId = self::activeBranchId();

        return $branchId !== null && app(BranchAccessService::class)->isMainBranchId($branchId);
    }
}
