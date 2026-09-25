<?php

namespace App\Support;

use App\NewPermKeys;
use App\Services\BranchAccessService;
use App\Services\BranchDataSharingService;
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

    /**
     * الفرع المختار يدوياً من شاشة التقرير — ذاكرة مؤقتة داخل الطلب الواحد.
     * المفتاح يضم قيمة branch_id المطلوبة، فتغيّرها بين طلبين يُبطل الذاكرة.
     */
    private static ?int $resolvedReportBranchId = null;
    private static ?string $resolvedReportKey = null;

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
        self::$resolvedReportKey = null;
        self::$resolvedReportBranchId = null;
        BranchDataSharingService::flush();
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

    /**
     * الفرع الذي يجب أن يُقيَّد به التقرير الآن.
     *
     * يختلف عن activeBranchId() في حالة واحدة: المحاسب الذي يرى كل الفروع
     * (إدارة عامة، أو موظف يملك branches_view_all). لا عزل تلقائي عليه —
     * وهو المطلوب، لأن رقمه الافتراضي هو رقم الشركة كلها — لكنه يحتاج أن
     * يفصل القوائم المالية وكشوفات الحساب على فرع بعينه عند المراجعة.
     * فيختار الفرع من شاشة التقرير (`branch_id`) فيُقرأ من هنا.
     *
     * موظف الفرع المقيّد لا يتأثر: عزله التلقائي يسبق الاختيار اليدوي، فلا
     * يستطيع الوصول إلى فرع غيره بتمرير branch_id في الرابط.
     */
    public static function reportBranchId(): ?int
    {
        $activeBranchId = self::activeBranchId();

        if ($activeBranchId !== null) {
            return $activeBranchId;
        }

        if (self::$suspensions > 0) {
            return null;
        }

        return self::selectedReportBranchId();
    }

    /** هل فرع التقرير الحالي هو الفرع الرئيسي؟ (يضمّ السجلات القديمة بلا فرع). */
    public static function reportBranchIsMain(): bool
    {
        $branchId = self::reportBranchId();

        return $branchId !== null && app(BranchAccessService::class)->isMainBranchId($branchId);
    }

    private static function selectedReportBranchId(): ?int
    {
        $admin = self::currentAdmin();
        $request = request();
        $key = ($admin ? 'admin:' . $admin->getAuthIdentifier() : 'guest')
            . '|' . (string) $request->input('branch_id', '');

        if (self::$resolvedReportKey === $key) {
            return self::$resolvedReportBranchId;
        }

        self::$resolvedReportKey = $key;
        self::$resolvedReportBranchId = self::resolveSelected($admin, $request);

        return self::$resolvedReportBranchId;
    }

    private static function resolveSelected($admin, $request): ?int
    {
        if (app()->runningInConsole() && ! app()->runningUnitTests()) {
            return null;
        }

        if (! $admin || ! SystemModuleManager::isEnabled('branches')) {
            return null;
        }

        // اختيار الفرع في التقرير قراءة فقط. في طلبات الكتابة يحمل `branch_id`
        // معنى آخر تماماً — الفرع الذي يُنشأ فيه القيد أو الحساب — فقراءته هنا
        // كانت ستفلتر استعلامات الأرصدة المصاحبة للحفظ على فرع السجل الجديد.
        if (! $request->isMethodSafe()) {
            return null;
        }

        return app(BranchAccessService::class)->selectedBranchId($request, $admin);
    }
}
