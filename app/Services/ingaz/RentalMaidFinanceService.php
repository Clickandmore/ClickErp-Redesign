<?php

namespace App\Services\ingaz;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

class RentalMaidFinanceService
{
    private const ACCOUNT_DEFINITIONS = [
        ['setting_key' => 'rental_maid_account_recruitment_cost', 'code' => 5911, 'fallback_name' => 'تكلفة استقدام عمالة تأجير', 'default_amount' => 0],
        ['setting_key' => 'rental_maid_account_visa_fee', 'code' => 5921, 'fallback_name' => 'رسوم تأشيرات عمل التأجير', 'default_amount' => 2000],
        ['setting_key' => 'rental_maid_account_enjaz_fee', 'code' => 5922, 'fallback_name' => 'رسوم إنجاز التأجير', 'default_amount' => 133],
        ['setting_key' => 'rental_maid_account_iqama_fee', 'code' => 5923, 'fallback_name' => 'رسوم إقامات التأجير', 'default_amount' => 0],
        ['setting_key' => 'rental_maid_account_work_permit_fee', 'code' => 5924, 'fallback_name' => 'رسوم رخص عمل التأجير', 'default_amount' => 0],
        ['setting_key' => 'rental_maid_account_medical_insurance', 'code' => 5925, 'fallback_name' => 'رسوم تأمين طبي التأجير', 'default_amount' => 0],
        ['setting_key' => 'rental_maid_account_social_insurance', 'code' => 5926, 'fallback_name' => 'رسوم تأمينات اجتماعية التأجير', 'default_amount' => 0],
        ['setting_key' => 'rental_maid_account_salary', 'code' => 5931, 'fallback_name' => 'رواتب عمالة التأجير', 'default_amount' => 0],
    ];

    public static function confirmSettingTitles(): array
    {
        return array_column(self::ACCOUNT_DEFINITIONS, 'setting_key');
    }

    public function getSettingDefinitions(?Collection $settings = null): array
    {
        $settings ??= $this->getSettings();

        return collect(self::ACCOUNT_DEFINITIONS)
            ->map(function (array $definition) use ($settings) {
                $account = $this->resolveAccountForDefinition($definition, $settings);

                return $definition + [
                    'display_label' => $definition['fallback_name'],
                    'selected_account_id' => $account?->id,
                ];
            })
            ->all();
    }

    public function getFormItems(array $submittedItems = [], ?Collection $settings = null): array
    {
        $settings ??= $this->getSettings();
        $submittedItemsByAccount = collect($submittedItems)
            ->filter(fn ($item) => filled($item['account_id'] ?? null))
            ->keyBy(fn ($item) => (string) ((int) $item['account_id']));

        return collect(self::ACCOUNT_DEFINITIONS)
            ->map(function (array $definition) use ($settings, $submittedItemsByAccount) {
                $account = $this->resolveAccountForDefinition($definition, $settings);

                if (!$account) {
                    return null;
                }

                $submittedItem = $submittedItemsByAccount->get((string) $account->id, []);

                return [
                    'id' => isset($submittedItem['id']) && is_numeric($submittedItem['id']) ? (int) $submittedItem['id'] : null,
                    'account_id' => (int) $account->id,
                    'account_code' => (string) $account->code,
                    'type' => $this->normalizeItemType($submittedItem['type'] ?? 'expense'),
                    'label' => $account->name,
                    'amount' => $this->normalizeAmount($submittedItem['amount'] ?? ($definition['default_amount'] ?? 0)),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    public function resolveRequestItems(Request $request): array
    {
        $submittedItems = collect((array) $request->input('rental_finance_items', []));
        $configuredItemsByAccount = collect($this->getFormItems())->keyBy('account_id');

        return $submittedItems
            ->map(function ($item) use ($configuredItemsByAccount) {
                $accountId = (int) ($item['account_id'] ?? 0);

                if ($accountId <= 0) {
                    return null;
                }

                $configuredItem = $configuredItemsByAccount->get($accountId);
                $account = Account::query()
                    ->select(['id', 'code', 'name'])
                    ->whereDoesntHave('children')
                    ->find($accountId);

                if (!$account) {
                    return null;
                }

                return [
                    'id' => isset($item['id']) && is_numeric($item['id']) ? (int) $item['id'] : null,
                    'account_id' => (int) $account->id,
                    'account_code' => (string) $account->code,
                    'type' => $this->normalizeItemType($item['type'] ?? ($configuredItem['type'] ?? 'expense')),
                    'label' => $account->name,
                    'amount' => $this->normalizeAmount($item['amount'] ?? 0),
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    private function getSettings(): Collection
    {
        return ConfirmSetting::query()
            ->whereIn('title', self::confirmSettingTitles())
            ->latest('updated_at')
            ->get()
            ->unique('title')
            ->keyBy('title');
    }

    private function resolveAccountForDefinition(array $definition, Collection $settings): ?Account
    {
        $selectedAccountId = (int) ($settings->get($definition['setting_key'])->value ?? 0);

        if ($selectedAccountId > 0) {
            $selectedAccount = Account::query()
                ->whereDoesntHave('children')
                ->find($selectedAccountId);

            if ($selectedAccount) {
                return $selectedAccount;
            }
        }

        return Account::query()
            ->whereDoesntHave('children')
            ->where('code', $definition['code'])
            ->first();
    }

    private function normalizeAmount(mixed $amount): float
    {
        return is_numeric($amount) ? round((float) $amount, 2) : 0.0;
    }

    private function normalizeItemType(?string $type): string
    {
        return $type === 'income' ? 'income' : 'expense';
    }
}
