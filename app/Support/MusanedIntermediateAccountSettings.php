<?php

namespace App\Support;

use App\Models\Branch;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\ConfirmSettingTypes;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

final class MusanedIntermediateAccountSettings
{
    public const MODE_ALL = 'all_branches';
    public const MODE_PER_BRANCH = 'per_branch';
    public const MODE_KEY = 'musaned_intermediate_account_mode';
    public const BRANCH_PREFIX = 'musaned_intermediate_account_branch_';

    public static function enabled(): bool
    {
        return SystemModuleManager::isEnabled('branches') && Schema::hasTable('branches');
    }

    public static function mode(): string
    {
        if (!self::enabled()) return self::MODE_ALL;
        $mode = ConfirmSetting::query()->where('title', self::MODE_KEY)->latest('updated_at')->value('value');
        return in_array($mode, [self::MODE_ALL, self::MODE_PER_BRANCH], true) ? $mode : self::MODE_ALL;
    }

    public static function settingsForForm(): array
    {
        if (!self::enabled()) return [];
        $rows = ConfirmSetting::query()
            ->where('title', 'like', self::BRANCH_PREFIX . '%')
            ->pluck('value', 'title');
        return Branch::query()->active()->ordered()->get(['id', 'name_ar', 'name_en', 'code'])
            ->mapWithKeys(fn (Branch $branch) => [$branch->id => $rows[self::branchKey($branch->id)] ?? null])
            ->all();
    }

    public static function branchKey(int $branchId): string
    {
        return self::BRANCH_PREFIX . $branchId;
    }

    public static function accountForBranch(?int $branchId): ?Account
    {
        if (!self::enabled() || self::mode() === self::MODE_ALL) {
            return self::findValid(self::globalAccountId());
        }
        if (!$branchId) return null;
        $id = ConfirmSetting::query()->where('title', self::branchKey($branchId))->latest('updated_at')->value('value');
        return self::findValid($id);
    }

    public static function globalAccountId(): ?int
    {
        $id = ConfirmSetting::query()
            ->where('title', ConfirmSettingTypes::MusanedIntermediateAccount->value)
            ->latest('updated_at')->value('value');
        return $id ? (int) $id : null;
    }

    public static function validateAccount(?int $accountId): bool
    {
        return $accountId !== null && self::findValid($accountId) !== null;
    }

    public static function validateSubmission(array $rows): void
    {
        $mode = collect($rows)->firstWhere('title', self::MODE_KEY)['value'] ?? self::mode();
        if (!self::enabled()) return;
        if (!in_array($mode, [self::MODE_ALL, self::MODE_PER_BRANCH], true)) {
            throw ValidationException::withMessages(['data.' . self::MODE_KEY . '.value' => 'اختر طريقة تحديد الحساب الوسيط لمساند.']);
        }

        $submitted = [];
        foreach ($rows as $row) {
            $title = (string) ($row['title'] ?? '');
            if (str_starts_with($title, self::BRANCH_PREFIX)) {
                $branchId = (int) substr($title, strlen(self::BRANCH_PREFIX));
                if (!Branch::query()->active()->whereKey($branchId)->exists()) {
                    throw ValidationException::withMessages(['data.' . $title . '.value' => 'الفرع المحدد غير موجود أو غير نشط.']);
                }
                $value = $row['value'] ?? null;
                if ($mode === self::MODE_PER_BRANCH && !self::validateAccount($value ? (int) $value : null)) {
                    throw ValidationException::withMessages(['data.' . $title . '.value' => 'حدد حسابًا وسيطًا صالحًا لهذا الفرع.']);
                }
                if ($value) $submitted[$branchId] = true;
            }
        }
        if ($mode === self::MODE_PER_BRANCH) {
            $missing = Branch::query()->active()->whereNotIn('id', array_keys($submitted))->exists();
            if ($missing) throw ValidationException::withMessages(['data.' . self::MODE_KEY . '.value' => 'حدد حسابًا وسيطًا لكل فرع نشط قبل اختيار الحسابات حسب الفرع.']);
        }
    }

    private static function findValid($id): ?Account
    {
        if (!$id) return null;
        return Account::query()->whereKey((int) $id)->whereDoesntHave('children')->musanedAccountCandidate()->first();
    }
}
