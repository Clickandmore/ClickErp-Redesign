<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const SETTING_KEY = 'rental_contract_exempt_salary_revenue_account';
    private const REVENUE_SETTING_KEY = 'rental_contract_revenue_account';
    private const ACCOUNT_NAME = 'راتب العاملة المعفى ضريبياً - عقود التأجير';

    public function up(): void
    {
        if (!Schema::hasTable('fn_accounts') || !Schema::hasTable('fn_confirm_settings')) {
            return;
        }

        $configuredId = $this->settingAccountId(self::SETTING_KEY);
        if ($configuredId && $this->isCreditorLeaf($configuredId)) {
            return;
        }

        $accountId = $this->findExistingSalaryAccountId() ?: $this->createSalaryAccount();
        if (!$accountId) {
            throw new RuntimeException('تعذر إنشاء أو اكتشاف حساب راتب العاملة المعفى لعقود التأجير.');
        }

        $this->storeSetting($accountId);
    }

    public function down(): void
    {
        // لا نحذف حساباً مالياً أو نفصل إعداداً قد استُخدم في قيود بعد النشر.
    }

    private function findExistingSalaryAccountId(): ?int
    {
        $query = DB::table('fn_accounts as account')
            ->where('account.status', 'creditor')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fn_accounts as child')
                    ->whereColumn('child.parent_id', 'account.id');
            })
            ->where(function ($query) {
                $query->where('account.name', self::ACCOUNT_NAME)
                    ->orWhere(function ($query) {
                        $query->where(function ($query) {
                            $query->where('account.name', 'like', '%راتب%عامل%')
                                ->orWhere('account.name', 'like', '%مرتب%عامل%');
                        })->where('account.name', 'like', '%معف%');
                    });
            })
            ->orderByRaw('CASE WHEN account.name = ? THEN 0 WHEN account.code = 4216 THEN 1 ELSE 2 END', [self::ACCOUNT_NAME]);

        $id = $query->value('account.id');

        return $id ? (int) $id : null;
    }

    private function createSalaryAccount(): ?int
    {
        $anchor = $this->anchorAccount();
        $parent = null;

        if ($anchor?->parent_id) {
            $parent = DB::table('fn_accounts')->where('id', $anchor->parent_id)->first();
        }

        if (!$parent) {
            $parent = DB::table('fn_accounts')
                ->where('status', 'creditor')
                ->whereIn('type', [42, 43])
                ->orderBy('level')
                ->orderBy('code')
                ->first();
        }

        if (!$parent) {
            $parent = DB::table('fn_accounts')
                ->where('status', 'creditor')
                ->where(function ($query) {
                    $query->where('name', 'like', '%إيراد%')
                        ->orWhere('name', 'like', '%ايراد%');
                })
                ->orderBy('level')
                ->orderBy('code')
                ->first();
        }

        $attributes = [
            'name' => self::ACCOUNT_NAME,
            'status' => 'creditor',
            'level' => $parent ? ((int) $parent->level + 1) : 1,
            'code' => $this->nextAccountCode($parent?->id, $parent?->code),
            'type' => 1,
            'parent_id' => $parent?->id,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        foreach (['operation_type', 'statement_type'] as $column) {
            if (Schema::hasColumn('fn_accounts', $column)) {
                $attributes[$column] = $anchor?->{$column} ?? $parent?->{$column} ?? null;
            }
        }

        if (Schema::hasColumn('fn_accounts', 'created_by_id')) {
            $attributes['created_by_id'] = Schema::hasTable('admins')
                ? DB::table('admins')->orderBy('id')->value('id')
                : null;
        }

        return (int) DB::table('fn_accounts')->insertGetId($attributes);
    }

    private function anchorAccount(): ?object
    {
        $configuredRevenueId = $this->settingAccountId(self::REVENUE_SETTING_KEY);
        if ($configuredRevenueId) {
            $account = DB::table('fn_accounts')->where('id', $configuredRevenueId)->first();
            if ($account && $account->status === 'creditor') {
                return $account;
            }
        }

        foreach ([
            'إيراد مقدم خدمات تأجير',
            'إيراد مقدم عمالة تأجير',
            'إيراد خدمات تأجير العمالة',
            'إيراد خدمات التأجير',
            'إيراد مبيعات التأجير',
        ] as $name) {
            $account = DB::table('fn_accounts as account')
                ->where('account.name', $name)
                ->where('account.status', 'creditor')
                ->whereNotExists(function ($query) {
                    $query->selectRaw('1')
                        ->from('fn_accounts as child')
                        ->whereColumn('child.parent_id', 'account.id');
                })
                ->first();

            if ($account) {
                return $account;
            }
        }

        return null;
    }

    private function nextAccountCode(?int $parentId, int|string|null $parentCode): int
    {
        $lastSiblingCode = DB::table('fn_accounts')->where('parent_id', $parentId)->max('code');
        $candidate = $lastSiblingCode
            ? ((int) $lastSiblingCode + 1)
            : (int) ((string) ($parentCode ?: '') . '1');

        $candidate = max(1, $candidate);
        while (DB::table('fn_accounts')->where('code', $candidate)->exists()) {
            $candidate++;
        }

        return $candidate;
    }

    private function settingAccountId(string $title): ?int
    {
        $value = DB::table('fn_confirm_settings')
            ->where('title', $title)
            ->orderByDesc('id')
            ->value('value');

        return is_numeric($value) && (int) $value > 0 ? (int) $value : null;
    }

    private function isCreditorLeaf(int $accountId): bool
    {
        return DB::table('fn_accounts as account')
            ->where('account.id', $accountId)
            ->where('account.status', 'creditor')
            ->whereNotExists(function ($query) {
                $query->selectRaw('1')
                    ->from('fn_accounts as child')
                    ->whereColumn('child.parent_id', 'account.id');
            })
            ->exists();
    }

    private function storeSetting(int $accountId): void
    {
        $existing = DB::table('fn_confirm_settings')
            ->where('title', self::SETTING_KEY)
            ->orderByDesc('id')
            ->first();
        $attributes = [
            'value' => (string) $accountId,
            'updated_at' => now(),
        ];

        if ($existing) {
            DB::table('fn_confirm_settings')->where('id', $existing->id)->update($attributes);
            DB::table('fn_confirm_settings')
                ->where('title', self::SETTING_KEY)
                ->where('id', '!=', $existing->id)
                ->delete();

            return;
        }

        $attributes['title'] = self::SETTING_KEY;
        $attributes['created_at'] = now();
        if (Schema::hasColumn('fn_confirm_settings', 'created_by_id') && Schema::hasTable('admins')) {
            $attributes['created_by_id'] = DB::table('admins')->orderBy('id')->value('id');
        }
        DB::table('fn_confirm_settings')->insert($attributes);
    }
};
