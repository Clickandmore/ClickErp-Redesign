<?php

namespace App\Support;

use Illuminate\Support\Facades\Auth;
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
 * - contract (الافتراضي، السلوك الحالي): عقود الاستقدام ـــ> عقد
 * - nationality_then_contract: عقود الاستقدام ـــ> الدولة ـــ> عقد
 *
 * البحث عن مركز قائم يسبق الإنشاء دائماً، ويغطي الشكلين، فتبديل الإعداد لا ينشئ
 * مركزاً ثانياً لعقد سبق أن أُنشئ له مركز. ولا يُعاد ترتيب أي مركز قائم.
 */
class RecruitmentCostCenters
{
    public const ROOT_TITLE = 'عقود الاستقدام';

    public static function forContract(int $contractId, ?string $nationalityName = null): CostCenter
    {
        $root = self::root();
        $nationalityName = trim((string) $nationalityName);

        $existing = CostCenter::query()
            ->lockForUpdate()
            ->whereIn('parent_id', self::lookupParentIds($root))
            ->where('table_name', CostCenterTypeEnum::General->value)
            ->where('table_related_id', $contractId)
            ->first();

        if ($existing) {
            return $existing;
        }

        $parent = ($nationalityName !== '' && RecruitmentSupplierSettings::groupsCostCentersByNationality())
            ? self::nationalityNode($root, $nationalityName)
            : $root;

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
     * مستوى الدولة: عقدة تحت الجذر باسم الجنسية و table_related_id = 0، تُعاد
     * استخدامها إن وُجدت (بعضها مُنشأ يدوياً من شاشة مراكز التكلفة).
     */
    public static function nationalityNode(CostCenter $root, string $nationalityName): CostCenter
    {
        $node = CostCenter::query()
            ->lockForUpdate()
            ->where('parent_id', $root->id)
            ->where('table_related_id', 0)
            ->where('title', $nationalityName)
            ->first();

        if ($node) {
            return $node;
        }

        return CostCenter::query()->create([
            'title' => $nationalityName,
            'parent_id' => $root->id,
            'table_name' => CostCenterTypeEnum::General->value,
            'table_related_id' => 0,
            'code' => self::nextCode(),
            'created_by_id' => self::actorId(),
        ]);
    }

    /**
     * الجذر ومستويات الدولة تحته. مقصود ألا يتسع البحث أكثر من ذلك: مراكز عقود
     * التأجير تستخدم نفس table_name مع أرقام صغيرة قد تتقاطع مع أرقام العقود.
     */
    private static function lookupParentIds(CostCenter $root): array
    {
        $nationalityIds = CostCenter::query()
            ->where('parent_id', $root->id)
            ->where('table_related_id', 0)
            ->pluck('id')
            ->all();

        return array_merge([$root->id], $nationalityIds);
    }

    private static function nextCode(): string
    {
        $lastCode = CostCenter::query()
            ->lockForUpdate()
            ->max('code');

        return (string) (((int) $lastCode) + 1);
    }

    private static function actorId(): int
    {
        return Auth::guard('admin')->id() ?: Auth::id() ?: 1;
    }
}
