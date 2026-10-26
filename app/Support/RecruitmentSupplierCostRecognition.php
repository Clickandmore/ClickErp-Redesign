<?php

namespace App\Support;

use App\Models\Contract;
use Illuminate\Support\Facades\Schema;
use Nami\Finance\Models\ConfirmSetting;

/**
 * لحظة إثبات فاتورة الوكيل الخارجي (إعداد من صفحة إعدادات الحسابات).
 *
 * - on_arrival (الافتراضي): عند تسجيل وصول العاملة. هذا هو السلوك الأصلي للنظام.
 * - on_dispatch: عند عمل إرسالية المكتب الخارجي، أي أول لحظة يكون فيها المبلغ والمكتب مؤكدين.
 * - on_contract: عند إنشاء العقد، ويلزمه مصدر مبلغ لا يعتمد على الإرسالية
 *   (RecruitmentSupplierSettings::SOURCE_OFFICE_PRICE_LIST). إن لم تكتمل بياناته لعقد
 *   بعينه (لا مكتب أو لا سعر) فلا يتعطل إنشاء العقد، بل يُؤجَّل القيد للمرحلة التالية.
 *
 * الوضع يُحفظ على العقد وقت إنشاء القيد، فالعقود التي أُثبتت تكلفتها قبل تغيير الإعداد
 * تُقفل بالطريقة التي أُثبتت بها.
 */
class RecruitmentSupplierCostRecognition
{
    public const SETTING_KEY = 'recruitment_supplier_cost_recognition';
    public const MODE_ON_ARRIVAL = 'on_arrival';
    public const MODE_ON_DISPATCH = 'on_dispatch';
    public const MODE_ON_CONTRACT = 'on_contract';

    public const CONTRACT_COLUMN = 'supplier_cost_recognition_mode';

    public static function modes(): array
    {
        return [self::MODE_ON_ARRIVAL, self::MODE_ON_DISPATCH, self::MODE_ON_CONTRACT];
    }

    public static function normalize($value): string
    {
        $value = is_string($value) ? trim($value) : '';

        return in_array($value, self::modes(), true) ? $value : self::MODE_ON_ARRIVAL;
    }

    public static function mode(): string
    {
        return self::normalize(ConfirmSetting::query()
            ->where('title', self::SETTING_KEY)->latest('updated_at')->value('value'));
    }

    public static function isOnContract(): bool
    {
        return self::mode() === self::MODE_ON_CONTRACT;
    }

    public static function contractColumnExists(): bool
    {
        return Schema::hasTable('contracts')
            && Schema::hasColumn('contracts', self::CONTRACT_COLUMN);
    }

    /**
     * الوضع الذي أُثبتت به تكلفة عقد بعينه. العقود القديمة بلا قيمة محفوظة تُعامل
     * على أنها «عند الوصول» لأن قيودها أُنشئت بهذه الطريقة.
     */
    public static function modeForContract(?Contract $contract): string
    {
        if (!$contract || !self::contractColumnExists()) {
            return self::MODE_ON_ARRIVAL;
        }

        return self::normalize($contract->getAttribute(self::CONTRACT_COLUMN));
    }
}
