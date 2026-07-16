<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Nationality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

class NationalityFinanceAccountService
{
    private const DEFINITIONS = [
        'nationality_recruitment_sales_parent_account' => [
            'label' => 'nationality_recruitment_sales_parent_account',
            'name' => 'مبيعات (%s)',
            'aliases' => ['مبيعات %s'],
        ],
        'nationality_recruitment_cost_parent_account' => [
            'label' => 'nationality_recruitment_cost_parent_account',
            'name' => 'تكاليف مبيعات (%s)',
            'aliases' => ['تكاليف مبيعات %s', 'تكلفة مبيعات %s', 'تكلفة مبيعات (%s)'],
        ],
        'nationality_contract_liability_parent_account' => [
            'label' => 'nationality_contract_liability_parent_account',
            'name' => 'التزامات عقود (%s)',
            'aliases' => ['التزامات عقود %s', 'إلتزامات عقود %s'],
        ],
        'nationality_sales_discount_parent_account' => [
            'label' => 'nationality_sales_discount_parent_account',
            'name' => 'خصم مبيعات %s',
            'aliases' => [],
        ],
        'nationality_transfer_revenue_parent_account' => [
            'label' => 'nationality_transfer_revenue_parent_account',
            'name' => 'ايراد نقل خدمات %s',
            'aliases' => ['إيراد نقل خدمات %s'],
        ],
        'nationality_job_order_expense_parent_account' => [
            'label' => 'nationality_job_order_expense_parent_account',
            'name' => 'مصروفات جوب اوردر %s',
            'aliases' => [],
        ],
        'nationality_embassy_expense_parent_account' => [
            'label' => 'nationality_embassy_expense_parent_account',
            'name' => 'مصروفات انهاء اجراءات سفارة %s',
            'aliases' => [],
        ],
        'nationality_recruitment_cost_return_parent_account' => [
            'label' => 'nationality_recruitment_cost_return_parent_account',
            'name' => 'مردودات تكلفة %s',
            'aliases' => [],
        ],
        'nationality_recruitment_sales_return_parent_account' => [
            'label' => 'nationality_recruitment_sales_return_parent_account',
            'name' => 'مردودات مبيعات استقدام %s',
            'aliases' => [],
        ],
        'nationality_transfer_sales_return_parent_account' => [
            'label' => 'nationality_transfer_sales_return_parent_account',
            'name' => 'مردودات نقل خدمات %s',
            'aliases' => [],
        ],
        'nationality_shelter_prepaid_expense_parent_account' => [
            'label' => 'nationality_shelter_prepaid_expense_parent_account',
            'name' => 'مصروفات مدفوعة مقدما ايواء %s',
            'aliases' => [],
        ],
        'nationality_transfer_advance_parent_account' => [
            'label' => 'nationality_transfer_advance_parent_account',
            'name' => 'دفعات مقدمة نقل خدمات %s',
            'aliases' => [],
        ],
    ];

    public static function confirmSettingTitles(): array
    {
        return array_keys(self::DEFINITIONS);
    }

    public function getSettingDefinitions(Collection $settings): array
    {
        $definitions = collect(self::DEFINITIONS)
            ->map(function (array $definition, string $settingKey) use ($settings) {
                return [
                    'setting_key' => $settingKey,
                    'label' => __('finance::finance.' . $definition['label']),
                    'selected_account_id' => $settings->get($settingKey)?->value,
                ];
            })
            ->values();

        return $this->attachSelectedAccountLabels($definitions)->all();
    }

    public function getNationalityAssignments(Collection $settings): array
    {
        $accounts = Account::query()
            ->with('parent:id,name')
            ->orderBy('code')
            ->get();

        $assignments = Nationality::query()
            ->with('translations')
            ->orderByTranslation('sub_title', 'asc')
            ->get()
            ->map(function (Nationality $nationality) use ($settings, $accounts) {
                $nationalityName = $this->resolveNationalityName($nationality);

                return [
                    'nationality_id' => $nationality->id,
                    'nationality_name' => $nationalityName,
                    'accounts' => collect(self::DEFINITIONS)
                        ->map(function (array $definition, string $parentSettingKey) use ($settings, $accounts, $nationality, $nationalityName) {
                            $settingKey = $this->assignmentSettingKey($nationality->id, $parentSettingKey);
                            $selectedAccountId = (int) ($settings->get($settingKey)?->value ?? 0);
                            $selectedAccount = $accounts->firstWhere('id', $selectedAccountId);

                            if (!$selectedAccount) {
                                $parentAccountId = (int) ($settings->get($parentSettingKey)?->value ?? 0);
                                $selectedAccount = $this->findExistingNationalityAccount(
                                    $accounts,
                                    $definition,
                                    $nationalityName,
                                    $parentAccountId ?: null
                                );
                            }

                            return [
                                'setting_key' => $settingKey,
                                'label' => __('finance::finance.' . $definition['label']),
                                'selected_account_id' => $selectedAccount?->id,
                                'selected_account_label' => $selectedAccount ? $this->accountLabel($selectedAccount) : null,
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            })
            ->values()
            ->all();

        return $assignments;
    }

    public function persistDetectedAssignments(Collection $settings, array $assignments): Collection
    {
        foreach ($assignments as $nationalityAssignment) {
            foreach ($nationalityAssignment['accounts'] as $accountAssignment) {
                $accountId = (int) ($accountAssignment['selected_account_id'] ?? 0);
                if ($accountId <= 0) {
                    continue;
                }

                $settingKey = $accountAssignment['setting_key'];
                if ((int) ($settings->get($settingKey)?->value ?? 0) === $accountId) {
                    continue;
                }

                $setting = $this->persistAssignment($settingKey, $accountId);
                $settings->put($settingKey, $setting);
            }
        }

        return $settings;
    }

    public function getAvailableParentAccounts(): Collection
    {
        return Account::query()->orderBy('code')->get();
    }

    public function searchAccounts(?string $search): Collection
    {
        $search = trim((string) $search);

        return Account::query()
            ->with('parent:id,name')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', '%' . $search . '%')
                        ->orWhere('code', 'like', '%' . $search . '%');
                });
            })
            ->orderBy('code')
            ->limit(50)
            ->get()
            ->map(fn (Account $account) => [
                'id' => $account->id,
                'text' => $this->accountLabel($account),
            ]);
    }

    public function generateFor(Nationality $nationality): array
    {
        $nationalityName = $this->resolveNationalityName($nationality);
        if ($nationalityName === '') {
            throw new \RuntimeException('لا يمكن إنشاء الحسابات لأن اسم الجنسية غير متوفر.');
        }

        $assignmentKeys = collect(array_keys(self::DEFINITIONS))
            ->map(fn (string $key) => $this->assignmentSettingKey($nationality->id, $key));

        $settings = ConfirmSetting::query()
            ->whereIn('title', array_merge(self::confirmSettingTitles(), $assignmentKeys->all()))
            ->latest('updated_at')
            ->get()
            ->unique('title')
            ->keyBy('title');

        $parentIds = collect(self::confirmSettingTitles())
            ->mapWithKeys(fn (string $key) => [$key => (int) ($settings->get($key)?->value ?? 0)]);

        $assignedIds = collect(array_keys(self::DEFINITIONS))->mapWithKeys(function (string $parentSettingKey) use ($settings, $nationality) {
            $assignmentKey = $this->assignmentSettingKey($nationality->id, $parentSettingKey);

            return [$parentSettingKey => (int) ($settings->get($assignmentKey)?->value ?? 0)];
        });

        $allAccountIds = $parentIds->merge($assignedIds)->filter()->unique()->values();
        $accountsById = Account::query()->whereIn('id', $allAccountIds)->get()->keyBy('id');
        $invalidAssignments = $assignedIds
            ->filter(fn (int $id) => $id > 0 && !$accountsById->has($id))
            ->keys();

        if ($invalidAssignments->isNotEmpty()) {
            throw new \RuntimeException('بعض حسابات الجنسية المختارة لم تعد موجودة في شجرة الحسابات. يرجى تحديث إعدادات الجنسية.');
        }

        $availableAccounts = Account::query()->orderBy('code')->get();
        foreach (self::DEFINITIONS as $parentSettingKey => $definition) {
            if ((int) $assignedIds->get($parentSettingKey) > 0) {
                continue;
            }

            $existingAccount = $this->findExistingNationalityAccount(
                $availableAccounts,
                $definition,
                $nationalityName,
                (int) $parentIds->get($parentSettingKey) ?: null
            );

            if ($existingAccount) {
                $assignedIds->put($parentSettingKey, $existingAccount->id);
                $accountsById->put($existingAccount->id, $existingAccount);
            }
        }

        $definitionsNeedingCreation = $assignedIds->filter(fn (int $id) => $id <= 0)->keys();
        $missing = $definitionsNeedingCreation
            ->filter(fn (string $key) => (int) $parentIds->get($key) <= 0 || !$accountsById->has($parentIds->get($key)))
            ->values();

        if ($missing->isNotEmpty()) {
            throw new \RuntimeException(
                'يرجى تحديد جميع حسابات الجنسية من إعدادات النظام المحاسبي قبل إنشاء الحسابات. الإعدادات الناقصة: '
                . $missing->map(fn (string $key) => __('finance::finance.' . self::DEFINITIONS[$key]['label']))->implode('، ')
            );
        }

        $created = 0;
        $existing = 0;

        $resolvedAccountIds = [];

        DB::transaction(function () use ($nationality, $nationalityName, $parentIds, $assignedIds, $accountsById, &$created, &$existing, &$resolvedAccountIds) {
            foreach (self::DEFINITIONS as $settingKey => $definition) {
                $assignedAccountId = (int) $assignedIds->get($settingKey);
                if ($assignedAccountId > 0) {
                    $resolvedAccountIds[$settingKey] = $assignedAccountId;
                    $existing++;
                    continue;
                }

                $parent = $accountsById->get($parentIds->get($settingKey));
                $names = collect(array_merge([$definition['name']], $definition['aliases']))
                    ->map(fn (string $pattern) => sprintf($pattern, $nationalityName))
                    ->all();

                $account = $this->findChildByNames($parent, $names);
                if ($account) {
                    $resolvedAccountIds[$settingKey] = $account->id;
                    $existing++;
                    continue;
                }

                $account = $this->createChildAccount($parent, sprintf($definition['name'], $nationalityName));
                $resolvedAccountIds[$settingKey] = $account->id;
                $created++;
            }

            foreach ($resolvedAccountIds as $parentSettingKey => $accountId) {
                $this->persistAssignment(
                    $this->assignmentSettingKey($nationality->id, $parentSettingKey),
                    $accountId
                );
            }
        });

        return [
            'nationality' => $nationalityName,
            'created_nodes' => $created,
            'created_leaves' => $created,
            'existing_nodes' => $existing,
            'account_ids' => $resolvedAccountIds,
        ];
    }

    private function assignmentSettingKey(int $nationalityId, string $parentSettingKey): string
    {
        $accountType = str_replace(['nationality_', '_parent_account'], '', $parentSettingKey);

        return 'nationality_account_' . $nationalityId . '_' . $accountType;
    }

    private function persistAssignment(string $settingKey, int $accountId): ConfirmSetting
    {
        $setting = ConfirmSetting::query()->updateOrCreate(
            ['title' => $settingKey],
            [
                'value' => $accountId,
                'created_by_id' => auth('admin')->id() ?: Auth::id() ?: 1,
            ]
        );

        ConfirmSetting::query()
            ->where('title', $settingKey)
            ->whereKeyNot($setting->id)
            ->delete();

        return $setting;
    }

    private function attachSelectedAccountLabels(Collection $definitions): Collection
    {
        $accounts = Account::query()
            ->whereIn('id', $definitions->pluck('selected_account_id')->filter()->unique())
            ->get()
            ->keyBy('id');

        return $definitions->map(function (array $definition) use ($accounts) {
            $account = $accounts->get((int) ($definition['selected_account_id'] ?? 0));
            $definition['selected_account_label'] = $account ? $this->accountLabel($account) : null;

            return $definition;
        });
    }

    private function accountLabel(Account $account): string
    {
        $label = trim((string) $account->code) . ' / ' . $account->name;
        if ($account->relationLoaded('parent') && $account->parent) {
            $label .= ' — ' . $account->parent->name;
        }

        return $label;
    }

    private function resolveNationalityName(Nationality $nationality): string
    {
        $nationality->loadMissing('translations');
        $arTranslation = $nationality->translations->firstWhere('locale', 'ar');

        return trim((string) (
            $arTranslation?->sub_title
            ?? $arTranslation?->title
            ?? $nationality->sub_title
            ?? $nationality->title
            ?? ''
        ));
    }

    private function findChildByNames(Account $parent, array $names): ?Account
    {
        $normalizedNames = collect($names)->map(fn (string $name) => $this->normalizeName($name));

        return Account::query()
            ->where('parent_id', $parent->id)
            ->get()
            ->first(fn (Account $account) => $normalizedNames->contains($this->normalizeName($account->name)));
    }

    private function findExistingNationalityAccount(
        Collection $accounts,
        array $definition,
        string $nationalityName,
        ?int $preferredParentId = null
    ): ?Account {
        if ($nationalityName === '') {
            return null;
        }

        $normalizedNames = collect(array_merge([$definition['name']], $definition['aliases']))
            ->map(fn (string $pattern) => $this->normalizeName(sprintf($pattern, $nationalityName)))
            ->unique();

        $matches = $accounts->filter(
            fn (Account $account) => $normalizedNames->contains($this->normalizeName($account->name))
        );

        if ($preferredParentId) {
            $preferred = $matches->firstWhere('parent_id', $preferredParentId);
            if ($preferred) {
                return $preferred;
            }
        }

        return $matches->first();
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim((string) preg_replace('/\s+/u', ' ', $name));

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $normalized);
    }

    private function createChildAccount(Account $parent, string $name): Account
    {
        $status = $parent->status instanceof \BackedEnum ? $parent->status->value : $parent->status;
        $type = $parent->type instanceof \BackedEnum ? $parent->type->value : $parent->type;

        return Account::query()->create([
            'name' => $name,
            'operation_type' => $parent->operation_type,
            'status' => $status,
            'level' => (int) $parent->level + 1,
            'type' => $type,
            'parent_id' => $parent->id,
            'code' => $this->nextChildCode($parent),
            'statement_type' => $parent->statement_type,
            'created_by_id' => auth('admin')->id() ?: Auth::id() ?: 1,
        ]);
    }

    private function nextChildCode(Account $parent): int
    {
        $lastChildCode = (int) Account::query()
            ->where('parent_id', $parent->id)
            ->lockForUpdate()
            ->max('code');

        if ($lastChildCode > 0) {
            return $lastChildCode + 1;
        }

        return (int) $parent->code > 0
            ? ((int) $parent->code * 100) + 1
            : ((int) Account::query()->lockForUpdate()->max('code')) + 1;
    }
}
