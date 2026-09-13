<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

/**
 * إعدادات محاسبة الإيواء والترحيل (قسم «حسابات الإيواء والترحيل» في صفحة إعدادات الحسابات).
 *
 * كل القيم تُقرأ من fn_confirm_settings. الحسابات تُعاد كمعرّف حساب طرفي موجود أو null،
 * والمفاتيح المنطقية تُعاد on/off مع قيمة افتراضية موثقة بجانب كل مفتاح.
 */
class ShelterAccountingSettings
{
    /** رسملة مصروف الإعاشة على أصل الإيواء بدل تحميله كمصروف فترة (افتراضي: on). */
    public const LIVING_EXPENSE_CAPITALIZE = 'shelter_living_expense_capitalize';

    /** تحميل رصيد الإيواء على المكتب الخارجي عند الترحيل (افتراضي: on، وإلا حساب الخسائر). */
    public const DEPORTATION_LIVING_CHARGE_OFFICE = 'deportation_living_charge_office';

    public const DEPORTATION_CHARGE_EXTERNAL_OFFICE = 'deportation_charge_external_office';

    /** حساب مدين بديل لقيد الترحيل عندما لا يُحمَّل المكتب الخارجي أو لا مكتب على العقد. */
    public const DEPORTATION_LOSS_ACCOUNT = 'deportation_loss_account';

    /** حساب مدين لتصفية رصيد إيواء بلا مسار خروج. */
    public const WRITE_OFF_ACCOUNT = 'shelter_write_off_account';

    /** مصروفات مدفوعة مقدماً - إيواء التجربة (1541 في دليل القيود). */
    public const TRIAL_PREPAID_ACCOUNT = 'shelter_trial_prepaid_account';

    /** إيراد استرداد مبيعات (4217): خصم مدة البقاء وأيام الاستخدام. */
    public const SALES_RECOVERY_INCOME_ACCOUNT = 'sales_recovery_income_account';

    /** حساب إيراد غرامة إلغاء عقود الاستقدام (الافتراضي في الدليل 4217 نفسه). */
    public const REFUND_PENALTY_ACCOUNT = 'recruitment_refund_penalty_account';

    public const TOGGLE_KEYS = [
        self::DEPORTATION_LIVING_CHARGE_OFFICE,
        self::LIVING_EXPENSE_CAPITALIZE,
        self::DEPORTATION_CHARGE_EXTERNAL_OFFICE,
    ];

    public const ACCOUNT_KEYS = [
        self::DEPORTATION_LOSS_ACCOUNT,
        self::WRITE_OFF_ACCOUNT,
        self::TRIAL_PREPAID_ACCOUNT,
        self::SALES_RECOVERY_INCOME_ACCOUNT,
        self::REFUND_PENALTY_ACCOUNT,
    ];

    private const TOGGLE_DEFAULTS = [
        self::DEPORTATION_LIVING_CHARGE_OFFICE => 'off',
        self::LIVING_EXPENSE_CAPITALIZE => 'on',
        self::DEPORTATION_CHARGE_EXTERNAL_OFFICE => 'on',
    ];

    public static function confirmSettingTitles(): array
    {
        return array_merge(self::TOGGLE_KEYS, self::ACCOUNT_KEYS);
    }

    public static function livingExpenseCapitalized(): bool
    {
        return self::toggle(self::LIVING_EXPENSE_CAPITALIZE);
    }

    public static function deportationChargesExternalOffice(): bool
    {
        return self::toggle(self::DEPORTATION_CHARGE_EXTERNAL_OFFICE);
    }

    public static function deportationLivingChargesOffice(): bool
    {
        return self::toggle(self::DEPORTATION_LIVING_CHARGE_OFFICE);
    }

    public static function deportationLossAccountId(): ?int
    {
        return self::accountId(self::DEPORTATION_LOSS_ACCOUNT);
    }

    public static function writeOffAccountId(): ?int
    {
        return self::accountId(self::WRITE_OFF_ACCOUNT);
    }

    public static function trialPrepaidAccountId(): ?int
    {
        return self::accountId(self::TRIAL_PREPAID_ACCOUNT);
    }

    public static function salesRecoveryIncomeAccountId(): ?int
    {
        return self::accountId(self::SALES_RECOVERY_INCOME_ACCOUNT);
    }

    public static function refundPenaltyAccountId(): ?int
    {
        return self::accountId(self::REFUND_PENALTY_ACCOUNT);
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

    public static function accountId(string $key): ?int
    {
        $value = self::rawValue($key);
        if (!is_numeric($value) || (int) $value <= 0) {
            return null;
        }

        $exists = Account::query()
            ->whereKey((int) $value)
            ->whereDoesntHave('children')
            ->exists();

        return $exists ? (int) $value : null;
    }

    public static function rawValue(string $key)
    {
        return ConfirmSetting::query()
            ->where('title', $key)
            ->latest('updated_at')
            ->value('value');
    }
}
