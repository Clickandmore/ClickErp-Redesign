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
            'name' => 'مبيعات %s',
            'aliases' => ['مبيعات (%s)', 'إيرادات %s', 'ايرادات %s', 'ايردات %s'],
            'parent_path' => [
                ['name' => 'الإيرادات', 'aliases' => ['الايرادات']],
                ['name' => 'اجمالى ايراد المبيعات', 'aliases' => ['إجمالي إيراد المبيعات', 'اجمالي ايراد المبيعات']],
                ['name' => 'مبيعات الاستقدام', 'aliases' => []],
            ],
        ],
        'nationality_recruitment_cost_parent_account' => [
            'label' => 'nationality_recruitment_cost_parent_account',
            'name' => 'تكلفة مبيعات %s',
            'aliases' => ['تكاليف مبيعات %s', 'تكاليف مبيعات (%s)', 'تكلفة مبيعات (%s)'],
            'parent_path' => [
                ['name' => 'المصروفات', 'aliases' => []],
                ['name' => 'اجمالى تكاليف المبيعات', 'aliases' => ['إجمالي تكاليف المبيعات', 'اجمالي تكاليف المبيعات']],
                ['name' => 'تكاليف المبيعات', 'aliases' => []],
            ],
        ],
        'nationality_contract_liability_parent_account' => [
            'label' => 'nationality_contract_liability_parent_account',
            'name' => 'التزامات عقود (%s)',
            'aliases' => ['التزامات عقود %s', 'إلتزامات عقود %s'],
            'parent_path' => [
                ['name' => 'الخصوم', 'aliases' => []],
                ['name' => 'الخصوم قصيرة الأجل', 'aliases' => ['الخصوم قصيرة الاجل']],
                ['name' => 'التزامات عقود الاستقدام', 'aliases' => ['إلتزامات عقود الاستقدام']],
                ['name' => 'التزامات عقود', 'aliases' => ['إلتزامات عقود']],
            ],
        ],
        'nationality_sales_discount_parent_account' => [
            'label' => 'nationality_sales_discount_parent_account',
            'name' => 'خصم مبيعات %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'المصروفات', 'aliases' => []],
                ['name' => 'اجمالى مصاريف التشغيل', 'aliases' => ['إجمالي مصاريف التشغيل', 'اجمالي مصاريف التشغيل']],
                ['name' => 'المصروفات العمومية والادارية', 'aliases' => ['المصروفات العمومية والإدارية']],
            ],
        ],
        'nationality_transfer_revenue_parent_account' => [
            'label' => 'nationality_transfer_revenue_parent_account',
            'name' => 'ايراد نقل خدمات %s',
            'aliases' => ['إيراد نقل خدمات %s'],
            'parent_path' => [
                ['name' => 'الإيرادات', 'aliases' => ['الايرادات']],
                ['name' => 'إيراد نقل الخدمات', 'aliases' => []],
                ['name' => 'ايرادات نقل خدمات', 'aliases' => ['إيراد نقل خدمات']],
            ],
        ],
        'nationality_job_order_expense_parent_account' => [
            'label' => 'nationality_job_order_expense_parent_account',
            'name' => 'مصروفات جوب اوردر %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'المصروفات', 'aliases' => []],
                ['name' => 'مصروفات تشغيلية اخرى', 'aliases' => []],
                ['name' => 'مصروفات سفارات اجنبية', 'aliases' => ['مصروفات سفارات أجنبية']],
            ],
        ],
        'nationality_embassy_expense_parent_account' => [
            'label' => 'nationality_embassy_expense_parent_account',
            'name' => 'مصروفات انهاء اجراءات سفارة %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'المصروفات', 'aliases' => []],
                ['name' => 'مصروفات تشغيلية اخرى', 'aliases' => []],
                ['name' => 'مصروفات سفارات اجنبية', 'aliases' => ['مصروفات سفارات أجنبية']],
            ],
        ],
        'nationality_recruitment_cost_return_parent_account' => [
            'label' => 'nationality_recruitment_cost_return_parent_account',
            'name' => 'مردودات تكلفة %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'المصروفات', 'aliases' => []],
                ['name' => 'تكاليف مبيعات الاستقدام', 'aliases' => []],
                ['name' => 'مردودات تكلفة الاستقدام', 'aliases' => []],
            ],
        ],
        'nationality_recruitment_sales_return_parent_account' => [
            'label' => 'nationality_recruitment_sales_return_parent_account',
            'name' => 'مردودات مبيعات استقدام %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'الإيرادات', 'aliases' => ['الايرادات']],
                ['name' => 'إيراد مبيعات الاستقدام', 'aliases' => ['إيرادات مبيعات الاستقدام']],
                ['name' => 'مردودات مبيعات الاستقدام', 'aliases' => ['مرتد مبيعات الاستقدام']],
            ],
        ],
        'nationality_transfer_sales_return_parent_account' => [
            'label' => 'nationality_transfer_sales_return_parent_account',
            'name' => 'مردودات نقل خدمات %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'الإيرادات', 'aliases' => ['الايرادات']],
                ['name' => 'إيراد نقل الخدمات', 'aliases' => []],
                ['name' => 'مردودات مبيعات نقل الخدمات', 'aliases' => []],
            ],
        ],
        'nationality_shelter_prepaid_expense_parent_account' => [
            'label' => 'nationality_shelter_prepaid_expense_parent_account',
            'name' => 'مصروفات مدفوعة مقدما ايواء %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'الأصول', 'aliases' => []],
                ['name' => 'الأصول المتداولة قصيرة الأجل', 'aliases' => ['الأصول المتداولة قصيرة الاجل']],
                ['name' => 'مصروفات مدفوعة مقدما - الايواء', 'aliases' => ['مصروفات مدفوعة مقدما الايواء']],
            ],
        ],
        'nationality_transfer_advance_parent_account' => [
            'label' => 'nationality_transfer_advance_parent_account',
            'name' => 'دفعات مقدمة نقل خدمات %s',
            'aliases' => [],
            'parent_path' => [
                ['name' => 'الخصوم', 'aliases' => []],
                ['name' => 'الخصوم قصيرة الأجل', 'aliases' => ['الخصوم قصيرة الاجل']],
                ['name' => 'دفعات مقدمة نقل خدمات', 'aliases' => []],
            ],
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
        $nationalities = Nationality::query()
            ->with('translations')
            ->orderByTranslation('sub_title', 'asc')
            ->get();
        $nationalityTerms = $nationalities
            ->map(fn (Nationality $nationality) => $this->resolveNationalityName($nationality))
            ->filter()
            ->flatMap(fn (string $name) => $this->nationalityNameAliases($name))
            ->unique()
            ->values();
        $selectedAccountIds = $settings
            ->filter(fn ($setting, string $title) => str_starts_with($title, 'nationality_account_'))
            ->pluck('value')
            ->filter()
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values();
        $accounts = Account::query()
            ->select(['id', 'parent_id', 'code', 'name'])
            ->with('parent:id,name')
            ->where(function ($query) use ($nationalityTerms, $selectedAccountIds) {
                $hasCondition = false;

                if ($selectedAccountIds->isNotEmpty()) {
                    $query->whereIn('id', $selectedAccountIds);
                    $hasCondition = true;
                }

                foreach ($nationalityTerms as $term) {
                    $method = $hasCondition ? 'orWhere' : 'where';
                    $query->{$method}('name', 'like', '%' . $term . '%');
                    $hasCondition = true;
                }

                if (!$hasCondition) {
                    $query->whereRaw('1 = 0');
                }
            })
            ->orderBy('code')
            ->get();

        $assignments = $nationalities
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

        $allAccountIds = $parentIds->values()
            ->concat($assignedIds->values())
            ->filter()
            ->unique()
            ->values();
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

        $created = 0;
        $createdLeaves = 0;
        $existing = 0;

        $resolvedAccountIds = [];

        DB::transaction(function () use ($nationality, $nationalityName, $parentIds, $assignedIds, $accountsById, &$created, &$createdLeaves, &$existing, &$resolvedAccountIds) {
            foreach (self::DEFINITIONS as $settingKey => $definition) {
                $parent = $accountsById->get($parentIds->get($settingKey));
                if (!$parent) {
                    $pathResult = $this->ensureAccountPath($definition['parent_path']);
                    $parent = $pathResult['account'];
                    $created += $pathResult['created_nodes'];
                    $parentIds->put($settingKey, $parent->id);
                    $accountsById->put($parent->id, $parent);
                    $this->persistAssignment($settingKey, $parent->id);
                }

                $assignedAccountId = (int) $assignedIds->get($settingKey);
                if ($assignedAccountId > 0) {
                    $resolvedAccountIds[$settingKey] = $assignedAccountId;
                    $existing++;
                    continue;
                }

                $names = $this->formattedAccountNames($definition, $nationalityName);

                $account = $this->findChildByNames($parent, $names);
                if ($account) {
                    $resolvedAccountIds[$settingKey] = $account->id;
                    $existing++;
                    continue;
                }

                $account = $this->createChildAccount($parent, sprintf($definition['name'], $nationalityName));
                $resolvedAccountIds[$settingKey] = $account->id;
                $created++;
                $createdLeaves++;
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
            'created_leaves' => $createdLeaves,
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

    private function ensureAccountPath(array $path): array
    {
        $parent = null;
        $createdNodes = 0;

        foreach ($path as $index => $node) {
            $names = array_merge([$node['name']], $node['aliases'] ?? []);
            $account = $this->findAccountByNames($names, $parent?->id);

            if (!$account) {
                if ($index === 0) {
                    throw new \RuntimeException('الحساب الرئيسي "' . $node['name'] . '" غير موجود في الشجرة المالية.');
                }

                $account = $this->createChildAccount($parent, $node['name']);
                $createdNodes++;
            }

            $parent = $account;
        }

        return [
            'account' => $parent,
            'created_nodes' => $createdNodes,
        ];
    }

    private function findAccountByNames(array $names, ?int $parentId): ?Account
    {
        $normalizedNames = collect($names)
            ->filter()
            ->map(fn (string $name) => $this->normalizeName($name))
            ->unique();

        return Account::query()
            ->when(
                $parentId === null,
                fn ($query) => $query->whereNull('parent_id'),
                fn ($query) => $query->where('parent_id', $parentId)
            )
            ->orderBy('code')
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

        $normalizedNames = collect($this->formattedAccountNames($definition, $nationalityName))
            ->map(fn (string $name) => $this->normalizeName($name))
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

    private function formattedAccountNames(array $definition, string $nationalityName): array
    {
        $patterns = array_merge([$definition['name']], $definition['aliases']);

        return collect($this->nationalityNameAliases($nationalityName))
            ->flatMap(fn (string $name) => collect($patterns)->map(
                fn (string $pattern) => sprintf($pattern, $name)
            ))
            ->unique()
            ->values()
            ->all();
    }

    private function nationalityNameAliases(string $nationalityName): array
    {
        $normalized = $this->normalizeName($nationalityName);
        $aliases = [$nationalityName];

        if (in_array($normalized, ['سيريلانكا', 'سريلانكا'], true)) {
            $aliases = array_merge($aliases, ['سيريلانكا', 'سريلانكا']);
        }

        return array_values(array_unique($aliases));
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
