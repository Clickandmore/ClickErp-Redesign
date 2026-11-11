<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\CostCenterTypeEnum;
use Nami\Finance\Models\CostCenter;

/**
 * مركز تكلفة عقد الاستقدام، في مكان واحد.
 *
 * كان هذا المنطق مكرراً في InvoiceController و MusanedTransferController و
 * RecruitmentTripAccountingService بثلاث نسخ متطابقة، فأي اختلاف في شكل الشجرة بينها
 * ينتج مركزين لنفس العقد. توحيده شرط لإضافة مستوى الدولة.
 *
 * شكل الشجرة يتبع إعداد RecruitmentSupplierSettings::COST_CENTER_SCOPE:
 * - contract (الافتراضي): عقود الاستقدام ـــ> عقد
 * - nationality_then_contract: عقود الاستقدام ـــ> الدولة ـــ> عقد
 *
 * البحث عن مركز قائم يسبق الإنشاء دائماً ويغطي الشكلين، فلا يُنشأ مركز ثانٍ لنفس العقد.
 * ومع تفعيل مستوى الدولة يُنقل المركز القائم تحت دولته (أول مرة، أو إذا تغيّرت جنسية
 * العاملة)، والنقل يغيّر parent_id فقط فتبقى القيود مربوطة بنفس المركز.
 *
 * عقدة الدولة تُعرف بالاسم بعد توحيده، فتُعاد استخدام عقد النظام القديم
 * («طلبات استقدام دولة كينيا») بدل إنشاء عقدة مكررة بجانبها.
 */
class RecruitmentCostCenters
{
    public const ROOT_TITLE = 'عقود الاستقدام';

    /** عنوان عقدة الدولة الجديدة: «عقود استقدام كينيا». */
    public const NATIONALITY_TITLE_PREFIX = 'عقود استقدام ';

    /** بادئات عناوين عقد الدولة المعروفة (الحالية والمستوردة من النظام القديم). */
    private const NATIONALITY_TITLE_PREFIXES = ['طلبات استقدام دولة', 'عقود استقدام دولة', 'عقود استقدام'];

    public static function forContract(int $contractId, ?string $nationalityName = null): CostCenter
    {
        $root = self::root();
        $nationalityName = trim((string) $nationalityName);
        $grouped = $nationalityName !== '' && RecruitmentSupplierSettings::groupsCostCentersByNationality();

        $parentIds = self::lookupParentIds($root);
        $existing = CostCenter::query()
            ->lockForUpdate()
            ->whereIn('parent_id', $parentIds)
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contractId)
            ->first();

        if ($existing) {
            if ($grouped) {
                $node = self::nationalityNode($root, $nationalityName);
                if ((int) $existing->parent_id !== (int) $node->id) {
                    $existing->update(['parent_id' => $node->id]);
                }
            }

            return $existing;
        }

        $parent = $grouped ? self::nationalityNode($root, $nationalityName) : $root;

        $title = 'عقد استقدام #' . $contractId;
        if ($nationalityName !== '') {
            $title .= ' - ' . $nationalityName;
        }

        return CostCenter::query()->create([
            'title' => $title,
            'parent_id' => $parent->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => $contractId,
            'code' => self::nextCode(),
            'created_by_id' => self::actorId(),
        ]);
    }

    public static function root(): CostCenter
    {
        $root = CostCenter::query()
            ->lockForUpdate()
            ->whereNull('parent_id')
            ->where('title', self::ROOT_TITLE)
            ->first();

        if ($root) {
            return $root;
        }

        return CostCenter::query()->create([
            'title' => self::ROOT_TITLE,
            'parent_id' => null,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => self::nextCode(),
            'created_by_id' => self::actorId(),
        ]);
    }

    /**
     * عقدة الدولة تحت الجذر (table_related_id = 0). تُطابق بالاسم بعد التوحيد، فـ«الفلبين»
     * تطابق «طلبات استقدام دولة الفلبين» و«فلبين» و«عقود استقدام الفلبين». عند وجود أكثر من
     * عقدة مطابقة تُختار صاحبة أكثر مراكز تحتها، ثم الأقدم. إن لم توجد تُنشأ «عقود استقدام <الدولة>».
     */
    public static function nationalityNode(CostCenter $root, string $nationalityName): CostCenter
    {
        $existing = self::findNationalityNode($root, $nationalityName);
        if ($existing) {
            return $existing;
        }

        return CostCenter::query()->create([
            'title' => self::NATIONALITY_TITLE_PREFIX . trim($nationalityName),
            'parent_id' => $root->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => self::nextCode(),
            'created_by_id' => self::actorId(),
        ]);
    }

    public static function findNationalityNode(CostCenter $root, string $nationalityName): ?CostCenter
    {
        $wanted = self::normalizeName($nationalityName);
        if ($wanted === '') {
            return null;
        }

        return CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $root->id)
            ->where('table_related_id', 0)
            ->withCount('children')
            ->orderByDesc('children_count')
            ->orderBy('id')
            ->get()
            ->first(fn (CostCenter $node) => self::normalizeName(self::nationalityFromTitle($node->title)) === $wanted);
    }

    /**
     * عقد الدول تحت «عقود الاستقدام» التي فيها مراكز، لقوائم فلتر الجنسية.
     * العقد الفارغة (المكررة من النظام القديم) لا تظهر.
     *
     * @return array<int, array{id:int, title:string, name:string, children:int}>
     */
    public static function nationalityNodes(): array
    {
        $root = CostCenter::query()->whereNull('parent_id')->where('title', self::ROOT_TITLE)->first();
        if (!$root) {
            return [];
        }

        return CostCenter::query()
            ->where('parent_id', $root->id)
            ->where('table_related_id', 0)
            ->where('title', 'not like', 'عقد استقدام #%')
            ->withCount('children')
            ->having('children_count', '>', 0)
            ->get()
            ->map(fn (CostCenter $node) => [
                'id' => (int) $node->id,
                'title' => $node->title,
                'name' => self::nationalityFromTitle($node->title),
                'children' => (int) $node->children_count,
            ])
            ->sortBy('name')
            ->values()
            ->all();
    }

    /** اسم الدولة من عنوان العقدة بعد حذف البادئة المعروفة. */
    public static function nationalityFromTitle(string $title): string
    {
        $title = trim($title);
        foreach (self::NATIONALITY_TITLE_PREFIXES as $prefix) {
            if (mb_strpos($title, $prefix) === 0) {
                return trim(mb_substr($title, mb_strlen($prefix)));
            }
        }

        return $title;
    }

    /** توحيد الاسم للمقارنة: الهمزات والتاء المربوطة والألف المقصورة و«ال» التعريف والمسافات. */
    public static function normalizeName(string $name): string
    {
        $name = preg_replace('/[\x{064B}-\x{0652}\x{0640}]/u', '', trim($name));
        $name = strtr($name, ['أ' => 'ا', 'إ' => 'ا', 'آ' => 'ا', 'ة' => 'ه', 'ى' => 'ي']);
        $name = preg_replace('/\s+/u', '', $name);
        if (mb_strpos($name, 'ال') === 0 && mb_strlen($name) > 4) {
            $name = mb_substr($name, 2);
        }

        return mb_strtolower($name);
    }

    /**
     * الجذر ومستويات الدولة تحته. مقصود ألا يتسع البحث أكثر من ذلك: مراكز عقود
     * التأجير تستخدم نفس table_name مع أرقام صغيرة قد تتقاطع مع أرقام العقود.
     */
    public static function lookupParentIds(CostCenter $root): array
    {
        $nationalityIds = CostCenter::query()
            ->where('parent_id', $root->id)
            ->where('table_related_id', 0)
            ->pluck('id')
            ->all();

        return array_merge([$root->id], $nationalityIds);
    }

    /**
     * أكبر كود رقمي + 1. الأكواد فيها قيم نصية (RENTAL-999، ALR-3، BRANCHES-x)، و max() على
     * عمود نصي يرجع نصاً يتحول إلى صفر، فكان كل مركز جديد يأخذ الكود «1».
     */
    public static function nextCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->whereRaw("code REGEXP '^[0-9]+$'")
            ->max(DB::raw('CAST(code AS UNSIGNED)'));

        return (string) (((int) $lastCode) + 1);
    }

    private static function actorId(): int
    {
        return Auth::guard('admin')->id() ?: Auth::id() ?: 1;
    }
}
