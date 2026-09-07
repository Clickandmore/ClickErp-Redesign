<?php

namespace Nami\Finance\Services;

use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;
use Nami\Finance\Models\TaxSetting;

class TaxAccountResolver
{
    /** legacy account setting => tax definition setting */
    public const RATE_SETTINGS = [
        'revenue_tax_account' => 'tax_rate_revenue',
        'expense_tax_account' => 'tax_rate_expenditure',
    ];

    /**
     * حساب ترحيل الضريبة المعتمد للعمليات الجديدة.
     * الأولوية لتعريف الضريبة المختار، ثم الحساب القديم إذا كان التعريف غائباً أو غير صالح،
     * حتى لا تتوقف العمليات على قواعد لم يكتمل ضبطها بعد.
     */
    public function resolve(string $accountSetting): ?Account
    {
        foreach ($this->candidateAccountIds($accountSetting) as $accountId) {
            $account = $this->validAccount($accountSetting, $accountId);

            if ($account) {
                return $account;
            }
        }

        return null;
    }

    /**
     * الحسابات الحالية والقديمة، مع سجل التغييرات المحفوظ منذ تفعيل التوحيد.
     * تُستخدم لتفسير القيود التاريخية فقط — لا تُستخدم لترحيل عمليات جديدة —
     * حتى لا يُعاد تصنيف سطر ضريبة قديم كإيراد بعد تغيير الإعداد.
     */
    public function historicalAccountIds(string $accountSetting): array
    {
        $stored = DB::table('fn_confirm_settings')
            ->where('title', 'like', 'tax_history_' . $accountSetting . '_%')->pluck('value')->all();

        return array_values(array_unique(array_map('intval', array_filter(
            array_merge($this->candidateAccountIds($accountSetting), $stored)
        ))));
    }

    public function remember(string $accountSetting, int $actorId): void
    {
        foreach ($this->candidateAccountIds($accountSetting) as $id) {
            if (!$this->validAccount($accountSetting, (int) $id)) {
                continue;
            }
            DB::table('fn_confirm_settings')->updateOrInsert(
                ['title' => 'tax_history_' . $accountSetting . '_' . $id], [
                'value' => $id, 'created_by_id' => $actorId,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }
    }

    /** هل يختلف تعريف الضريبة المختار عن الحساب القديم؟ */
    public function conflict(string $accountSetting): ?array
    {
        [$fromDefinition, $legacy] = [
            $this->definitionAccountId($accountSetting),
            $this->legacyAccountId($accountSetting),
        ];

        if (!$fromDefinition || !$legacy || (int) $fromDefinition === (int) $legacy) {
            return null;
        }

        return ['definition' => (int) $fromDefinition, 'legacy' => (int) $legacy];
    }

    public function expectedStatus(string $accountSetting): string
    {
        return $accountSetting === 'revenue_tax_account' ? 'creditor' : 'debtor';
    }

    /** مرتبة حسب الأولوية: تعريف الضريبة ثم الحساب القديم. */
    private function candidateAccountIds(string $accountSetting): array
    {
        if (!isset(self::RATE_SETTINGS[$accountSetting])) {
            return [];
        }

        return array_values(array_filter([
            $this->definitionAccountId($accountSetting),
            $this->legacyAccountId($accountSetting),
        ]));
    }

    private function definitionAccountId(string $accountSetting): ?int
    {
        $taxSettingId = $this->settingValue(self::RATE_SETTINGS[$accountSetting] ?? '');

        return $taxSettingId
            ? (int) TaxSetting::query()->whereKey($taxSettingId)->value('account_id') ?: null
            : null;
    }

    private function legacyAccountId(string $accountSetting): ?int
    {
        return (int) $this->settingValue($accountSetting) ?: null;
    }

    private function settingValue(string $title)
    {
        return $title === '' ? null : ConfirmSetting::query()
            ->where('title', $title)
            ->latest('updated_at')
            ->latest('id')
            ->value('value');
    }

    /** حساب طرفي بطبيعة مطابقة للجانب المطلوب — لا تخمين بالاسم أو الكود. */
    private function validAccount(string $accountSetting, ?int $accountId): ?Account
    {
        return $accountId ? Account::query()
            ->whereKey($accountId)
            ->whereDoesntHave('children')
            ->where('status', $this->expectedStatus($accountSetting))
            ->first() : null;
    }
}
