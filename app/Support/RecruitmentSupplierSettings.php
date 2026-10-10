<?php

namespace App\Support;

use Nami\Finance\Models\ConfirmSetting;

/**
 * إعدادات قيود المشتريات والوكلاء الخارجيين (قسم «قيود المشتريات والوكلاء الخارجيون»
 * في صفحة إعدادات الحسابات).
 *
 * قاعدة هذا الملف: **كل قيمة افتراضية تساوي السلوك الحالي للنظام حرفياً**، فالإعداد الذي
 * لم يُحفظ قط لا يغيّر أي قيد ولا أي شاشة. لا يتغير شيء إلا بتبديل الخيار عن قصد.
 *
 * القيمة المختارة تُثبَّت على المستند لحظة إنشاء القيد (مثل invoices.revenue_recognition_mode)،
 * فالعقود والتحويلات السابقة تُقفل بالطريقة التي أُنشئت بها.
 */
class RecruitmentSupplierSettings
{
    /** مصدر مبلغ تكلفة الوكيل الخارجي. */
    public const COST_SOURCE = 'recruitment_supplier_cost_source';

    /** المبلغ يُدخل يدوياً على الإرسالية بالدولار (السلوك الحالي). */
    public const SOURCE_DISPATCH_MANUAL = 'dispatch_manual';

    /** المبلغ يأتي من تكلفة الدولار المعرّفة على ملف المكتب الخارجي. */
    public const SOURCE_OFFICE_PRICE_LIST = 'office_price_list';

    /** سعر صرف الدولار المستخدم في قيود تكلفة الوكيل. */
    public const USD_RATE = 'recruitment_supplier_usd_rate';

    /** السعر المثبَّت في الكود تاريخياً، وهو الافتراضي حتى يُحفظ غيره. */
    public const USD_RATE_DEFAULT = 3.75;

    /** مستوى شجرة مراكز التكلفة لعقود الاستقدام. */
    public const COST_CENTER_SCOPE = 'recruitment_cost_center_scope';

    /** مركز لكل عقد تحت جذر «عقود الاستقدام» مباشرة (السلوك الحالي). */
    public const SCOPE_CONTRACT = 'contract';

    /** مستوى وسيط لكل دولة: عقود الاستقدام ـــ> الدولة ـــ> العقد. */
    public const SCOPE_NATIONALITY_THEN_CONTRACT = 'nationality_then_contract';

    /** تمرير مركز تكلفة العقد لسطر حساب الوكيل الخارجي (التفصيل بالعاملة). */
    public const SUPPLIER_LINE_COST_CENTER = 'recruitment_supplier_line_cost_center';

    /** طريقة سداد دفعات الوكيل الخارجي. */
    public const PAYMENT_MODE = 'recruitment_supplier_payment_mode';

    /** سند صرف عام يدوي من شاشة المالية (السلوك الحالي). */
    public const PAYMENT_GENERAL_BOND = 'general_bond';

    /** شاشة دفعات مربوطة بالعقد والعاملة ومركز التكلفة. */
    public const PAYMENT_CONTRACT_LINKED = 'contract_linked';

    /** مسار قيد التحصيل من منصة مساند. */
    public const MUSANED_COLLECTION_MODE = 'musaned_collection_mode';

    /** قيد تسوية على حساب مساند الوسيط ثم قيد تحويل بنكي (السلوك الحالي). */
    public const MUSANED_INTERMEDIATE_TWO_STEP = 'intermediate_two_step';

    /** سند قبض واحد: مدين البنك المحصل / دائن حساب مساند، بلا حساب وسيط. */
    public const MUSANED_DIRECT_RECEIPT = 'direct_receipt';

    /** العمود الذي يُثبَّت عليه وضع التحصيل على كل تحويل مساند. */
    public const MUSANED_TRANSFER_COLUMN = 'collection_mode';

    /**
     * شاشة «الملف المالي للعقد»: الفاتورة وتحويل مساند وسداد الوكيل في مكان واحد.
     *
     * لوحة حالة ومُطلِق إجراءات فوق المستندات القائمة، لا نموذج مستند موازٍ. كل إجراء
     * يُرحَّل بتاريخه ووحده، لأن الثلاثة لا تقع في لحظة واحدة في الواقع.
     */
    public const CONTRACT_FINANCE_SCREEN = 'recruitment_contract_finance_screen';

    private const CHOICES = [
        self::COST_SOURCE => [self::SOURCE_DISPATCH_MANUAL, self::SOURCE_OFFICE_PRICE_LIST],
        self::COST_CENTER_SCOPE => [self::SCOPE_CONTRACT, self::SCOPE_NATIONALITY_THEN_CONTRACT],
        self::PAYMENT_MODE => [self::PAYMENT_GENERAL_BOND, self::PAYMENT_CONTRACT_LINKED],
        self::MUSANED_COLLECTION_MODE => [self::MUSANED_INTERMEDIATE_TWO_STEP, self::MUSANED_DIRECT_RECEIPT],
    ];

    /** الخيار الأول في كل مجموعة هو الافتراضي، وهو دائماً السلوك الحالي. */
    private const TOGGLE_DEFAULTS = [
        self::SUPPLIER_LINE_COST_CENTER => 'off',
        self::CONTRACT_FINANCE_SCREEN => 'off',
    ];

    public static function confirmSettingTitles(): array
    {
        return array_merge(
            array_keys(self::CHOICES),
            array_keys(self::TOGGLE_DEFAULTS),
            [self::USD_RATE]
        );
    }

    public static function choices(string $key): array
    {
        return self::CHOICES[$key] ?? [];
    }

    /**
     * أي قيمة غير معروفة أو فارغة تعود إلى الخيار الأول، أي إلى السلوك الحالي.
     */
    public static function normalize(string $key, $value): string
    {
        $choices = self::CHOICES[$key] ?? [];
        if (empty($choices)) {
            return '';
        }

        $value = is_string($value) ? trim($value) : '';

        return in_array($value, $choices, true) ? $value : $choices[0];
    }

    public static function mode(string $key): string
    {
        return self::normalize($key, self::rawValue($key));
    }

    public static function costSource(): string
    {
        return self::mode(self::COST_SOURCE);
    }

    public static function usesOfficePriceList(): bool
    {
        return self::costSource() === self::SOURCE_OFFICE_PRICE_LIST;
    }

    /**
     * سعر الصرف المحفوظ، أو 3.75 كما كان مثبتاً في الكود. القيم غير الموجبة تُهمل.
     */
    public static function usdRate(): float
    {
        $value = self::rawValue(self::USD_RATE);

        if (!is_numeric($value) || (float) $value <= 0) {
            return self::USD_RATE_DEFAULT;
        }

        return round((float) $value, 6);
    }

    public static function costCenterScope(): string
    {
        return self::mode(self::COST_CENTER_SCOPE);
    }

    public static function groupsCostCentersByNationality(): bool
    {
        return self::costCenterScope() === self::SCOPE_NATIONALITY_THEN_CONTRACT;
    }

    public static function stampsSupplierLineCostCenter(): bool
    {
        return self::toggle(self::SUPPLIER_LINE_COST_CENTER);
    }

    public static function contractFinanceScreenEnabled(): bool
    {
        return self::toggle(self::CONTRACT_FINANCE_SCREEN);
    }

    public static function paymentMode(): string
    {
        return self::mode(self::PAYMENT_MODE);
    }

    public static function usesContractLinkedPayments(): bool
    {
        return self::paymentMode() === self::PAYMENT_CONTRACT_LINKED;
    }

    public static function musanedCollectionMode(): string
    {
        return self::mode(self::MUSANED_COLLECTION_MODE);
    }

    public static function musanedUsesDirectReceipt(): bool
    {
        return self::musanedCollectionMode() === self::MUSANED_DIRECT_RECEIPT;
    }

    /**
     * وضع التحصيل المثبَّت على تحويل مساند بعينه. التحويلات القديمة بلا قيمة محفوظة
     * تُعامل على أنها الحساب الوسيط لأن قيودها أُنشئت بهذه الطريقة.
     */
    public static function musanedCollectionModeForTransfer($transfer): string
    {
        $stored = is_object($transfer)
            ? $transfer->getAttribute(self::MUSANED_TRANSFER_COLUMN)
            : null;

        return self::normalize(self::MUSANED_COLLECTION_MODE, $stored);
    }

    public static function toggle(string $key): bool
    {
        $value = self::rawValue($key);
        $value = is_string($value) ? strtolower(trim($value)) : '';

        if ($value === '') {
            $value = self::TOGGLE_DEFAULTS[$key] ?? 'off';
        }

        return $value === 'on';
    }

    public static function rawValue(string $key)
    {
        return ConfirmSetting::query()
            ->where('title', $key)
            ->latest('updated_at')
            ->value('value');
    }
}
