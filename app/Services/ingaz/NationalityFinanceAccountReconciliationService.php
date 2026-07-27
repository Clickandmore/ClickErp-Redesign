<?php

namespace App\Services\ingaz;

use App\Models\ingaz\Nationality;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Models\Account;
use Nami\Finance\Models\ConfirmSetting;

class NationalityFinanceAccountReconciliationService
{
    private int $mergedAccounts = 0;
    private int $movedAccounts = 0;
    private int $renamedAccounts = 0;
    private int $removedEmptyAccounts = 0;

    public function reconcile(bool $failWhenRequiredParentsMissing = true): array
    {
        $this->mergedAccounts = 0;
        $this->movedAccounts = 0;
        $this->renamedAccounts = 0;
        $this->removedEmptyAccounts = 0;

        return DB::transaction(function () use ($failWhenRequiredParentsMissing) {
            $salesParent = $this->findPath([
                ['الايرادات', 'الإيرادات'],
                ['اجمالى ايراد المبيعات', 'إجمالي إيراد المبيعات', 'اجمالي ايراد المبيعات'],
                ['مبيعات الاستقدام'],
            ]) ?? $this->accountFromSetting('nationality_recruitment_sales_parent_account');
            $costParent = $this->findPath([
                ['المصروفات'],
                ['اجمالى تكاليف المبيعات', 'إجمالي تكاليف المبيعات', 'اجمالي تكاليف المبيعات'],
                ['تكاليف المبيعات'],
            ]) ?? $this->accountFromSetting('nationality_recruitment_cost_parent_account');
            $discountParent = $this->findPath([
                ['المصروفات'],
                ['اجمالى مصاريف التشغيل', 'إجمالي مصاريف التشغيل', 'اجمالي مصاريف التشغيل'],
                ['المصروفات العمومية والادارية', 'المصروفات العمومية والإدارية'],
            ]) ?? $this->accountFromSetting('nationality_sales_discount_parent_account');

            if (!$salesParent || !$costParent || !$discountParent) {
                $missingParents = collect([
                    'sales' => $salesParent,
                    'cost' => $costParent,
                    'discount' => $discountParent,
                ])->filter(fn (?Account $account) => !$account)->keys()->all();

                if (!$failWhenRequiredParentsMissing) {
                    return [
                        'merged_accounts' => 0,
                        'moved_accounts' => 0,
                        'renamed_accounts' => 0,
                        'removed_empty_accounts' => 0,
                        'skipped' => true,
                        'missing_parents' => $missingParents,
                    ];
                }

                throw new \RuntimeException('تعذر العثور على الفروع المحاسبية الأصلية للمبيعات أو التكاليف أو الخصومات.');
            }

            $newRevenueRoot = $this->findPath([
                ['الايرادات', 'الإيرادات'],
                ['إيراد مبيعات الاستقدام', 'إيرادات مبيعات الاستقدام'],
            ]);
            $newCostRoot = $this->findPath([
                ['المصروفات'],
                ['تكاليف مبيعات الاستقدام'],
            ]);

            $this->persistSetting('nationality_recruitment_sales_parent_account', $salesParent->id);
            $this->persistSetting('nationality_recruitment_cost_parent_account', $costParent->id);
            $this->persistSetting('nationality_sales_discount_parent_account', $discountParent->id);

            foreach (Nationality::query()->with('translations')->get() as $nationality) {
                $nationalityName = $this->nationalityName($nationality);
                if ($nationalityName === '') {
                    continue;
                }

                $aliases = $this->nationalityAliases($nationalityName);
                $this->reconcileLeaves(
                    $nationality->id,
                    'recruitment_sales',
                    $salesParent,
                    'مبيعات ' . $nationalityName,
                    $this->formatNames(
                        ['مبيعات %s', 'مبيعات (%s)', 'إيرادات %s', 'ايرادات %s', 'ايردات %s'],
                        $aliases
                    )
                );
                $this->reconcileLeaves(
                    $nationality->id,
                    'recruitment_cost',
                    $costParent,
                    'تكلفة مبيعات ' . $nationalityName,
                    $this->formatNames(
                        ['تكلفة مبيعات %s', 'تكلفة مبيعات (%s)', 'تكاليف مبيعات %s', 'تكاليف مبيعات (%s)'],
                        $aliases
                    )
                );
                $this->reconcileLeaves(
                    $nationality->id,
                    'sales_discount',
                    $discountParent,
                    'خصم مبيعات ' . $nationalityName,
                    $this->formatNames(['خصم مبيعات %s'], $aliases)
                );
            }

            if ($newRevenueRoot) {
                $salesReturns = $this->findDirectChild($newRevenueRoot, [
                    'مردودات مبيعات الاستقدام',
                    'مرتد مبيعات الاستقدام',
                ]);
                if ($salesReturns) {
                    $this->moveAccount($salesReturns, $salesParent);
                    $this->persistSetting('nationality_recruitment_sales_return_parent_account', $salesReturns->id);
                }
            }

            if ($newCostRoot) {
                $costReturns = $this->findDirectChild($newCostRoot, ['مردودات تكلفة الاستقدام']);
                if ($costReturns) {
                    $this->moveAccount($costReturns, $costParent);
                    $this->persistSetting('nationality_recruitment_cost_return_parent_account', $costReturns->id);
                }

                $delegationCosts = Account::query()
                    ->whereIn('parent_id', $newCostRoot->children()->pluck('id'))
                    ->get()
                    ->first(fn (Account $account) => $this->namesMatch($account->name, ['تكاليف تفاويض']));
                if ($delegationCosts) {
                    $this->moveAccount($delegationCosts, $costParent);
                }
            }

            $liabilityParents = Account::query()
                ->get()
                ->filter(fn (Account $account) => $this->namesMatch($account->name, ['التزامات عقود', 'إلتزامات عقود']));
            $liabilityParent = $this->mostUsedTree($liabilityParents);
            if ($liabilityParent) {
                $this->moveMatchingNationalityLeaves($liabilityParents, $liabilityParent);
                $this->persistSetting('nationality_contract_liability_parent_account', $liabilityParent->id);
            }

            $advanceParents = Account::query()
                ->get()
                ->filter(fn (Account $account) => $this->namesMatch($account->name, ['دفعات مقدمة نقل خدمات']));
            $advanceParent = $this->parentInSameBranch($advanceParents, $liabilityParent)
                ?? $this->mostUsedTree($advanceParents);
            if ($advanceParent) {
                $this->moveMatchingNationalityLeaves($advanceParents, $advanceParent);
                $this->persistSetting('nationality_transfer_advance_parent_account', $advanceParent->id);
            }

            $cleanupRoots = collect([$newRevenueRoot, $newCostRoot])
                ->filter()
                ->merge($liabilityParents->reject(fn (Account $account) => $account->is($liabilityParent)))
                ->merge($advanceParents->reject(fn (Account $account) => $account->is($advanceParent)));

            foreach ($cleanupRoots as $root) {
                $parentId = $root->parent_id;
                $this->removeEmptyTree($root->fresh());
                $this->pruneEmptyAncestors($parentId);
            }

            return [
                'merged_accounts' => $this->mergedAccounts,
                'moved_accounts' => $this->movedAccounts,
                'renamed_accounts' => $this->renamedAccounts,
                'removed_empty_accounts' => $this->removedEmptyAccounts,
                'skipped' => false,
                'missing_parents' => [],
            ];
        });
    }

    private function reconcileLeaves(
        int $nationalityId,
        string $accountType,
        Account $preferredParent,
        string $canonicalName,
        array $names
    ): void {
        $normalizedNames = collect($names)->map(fn (string $name) => $this->normalize($name))->unique();
        $candidates = Account::query()
            ->withCount('constraintDetails')
            ->get()
            ->filter(fn (Account $account) => $normalizedNames->contains($this->normalize($account->name)));

        if ($candidates->isEmpty()) {
            return;
        }

        $canonical = $candidates
            ->where('parent_id', $preferredParent->id)
            ->sortByDesc('constraint_details_count')
            ->first()
            ?? $candidates->sortByDesc('constraint_details_count')->first();

        if ((int) $canonical->parent_id !== (int) $preferredParent->id) {
            $this->moveAccount($canonical, $preferredParent);
        }

        foreach ($candidates->reject(fn (Account $account) => $account->is($canonical)) as $duplicate) {
            $this->mergeAccount($duplicate, $canonical);
        }

        if ($canonical->name !== $canonicalName) {
            $canonical->update(['name' => $canonicalName]);
            $this->renamedAccounts++;
        }

        $this->persistSetting(
            'nationality_account_' . $nationalityId . '_' . $accountType,
            $canonical->id
        );
    }

    private function moveMatchingNationalityLeaves(Collection $parents, Account $targetParent): void
    {
        foreach ($parents->reject(fn (Account $parent) => $parent->is($targetParent)) as $parent) {
            foreach ($parent->children()->get() as $child) {
                if ($child->children()->exists()) {
                    continue;
                }

                $sameName = $targetParent->children()->get()
                    ->first(fn (Account $candidate) => $this->normalize($candidate->name) === $this->normalize($child->name));

                if ($sameName) {
                    $this->mergeAccount($child, $sameName);
                } else {
                    $this->moveAccount($child, $targetParent);
                }
            }
        }
    }

    private function mergeAccount(Account $duplicate, Account $canonical): void
    {
        if ($duplicate->is($canonical)) {
            return;
        }

        foreach ($this->referenceColumns() as $reference) {
            if ($reference->TABLE_NAME === 'fn_accounts' && $reference->COLUMN_NAME === 'parent_id') {
                continue;
            }

            DB::table($reference->TABLE_NAME)
                ->where($reference->COLUMN_NAME, $duplicate->id)
                ->update([$reference->COLUMN_NAME => $canonical->id]);
        }

        ConfirmSetting::query()
            ->where('value', (string) $duplicate->id)
            ->update(['value' => $canonical->id]);

        foreach ($duplicate->children()->get() as $child) {
            $this->moveAccount($child, $canonical);
        }

        $duplicate->delete();
        $this->mergedAccounts++;
    }

    private function moveAccount(Account $account, Account $newParent): void
    {
        if ((int) $account->parent_id === (int) $newParent->id) {
            return;
        }

        $account->update([
            'parent_id' => $newParent->id,
            'level' => (int) $newParent->level + 1,
            'code' => $this->nextChildCode($newParent),
            'status' => $newParent->status instanceof \BackedEnum ? $newParent->status->value : $newParent->status,
            'statement_type' => $newParent->statement_type,
        ]);
        $this->recodeChildren($account->fresh());
        $this->movedAccounts++;
    }

    private function recodeChildren(Account $parent): void
    {
        $nextCode = ((int) $parent->code) * 100;
        foreach ($parent->children()->orderBy('code')->get() as $child) {
            $child->update([
                'code' => $nextCode++,
                'level' => (int) $parent->level + 1,
            ]);
            $this->recodeChildren($child->fresh());
        }
    }

    private function nextChildCode(Account $parent): int
    {
        $lastCode = (int) Account::query()->where('parent_id', $parent->id)->max('code');

        return $lastCode > 0 ? $lastCode + 1 : ((int) $parent->code * 100);
    }

    private function removeEmptyTree(Account $account): bool
    {
        foreach ($account->children()->get() as $child) {
            $this->removeEmptyTree($child);
        }

        $account->refresh();
        if ($account->children()->exists() || $this->hasReferences($account)) {
            return false;
        }

        ConfirmSetting::query()->where('value', (string) $account->id)->update(['value' => null]);
        $account->delete();
        $this->removedEmptyAccounts++;

        return true;
    }

    private function pruneEmptyAncestors(?int $accountId): void
    {
        while ($accountId) {
            $account = Account::query()->find($accountId);
            if (!$account || !$account->parent_id || $account->children()->exists() || $this->hasReferences($account)) {
                return;
            }

            $parentId = $account->parent_id;
            ConfirmSetting::query()->where('value', (string) $account->id)->update(['value' => null]);
            $account->delete();
            $this->removedEmptyAccounts++;
            $accountId = $parentId;
        }
    }

    private function hasReferences(Account $account): bool
    {
        foreach ($this->referenceColumns() as $reference) {
            if ($reference->TABLE_NAME === 'fn_accounts' && $reference->COLUMN_NAME === 'parent_id') {
                continue;
            }

            if (DB::table($reference->TABLE_NAME)->where($reference->COLUMN_NAME, $account->id)->exists()) {
                return true;
            }
        }

        return false;
    }

    private function referenceColumns(): Collection
    {
        $database = DB::getDatabaseName();
        $references = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->where('REFERENCED_TABLE_SCHEMA', $database)
            ->where('REFERENCED_TABLE_NAME', 'fn_accounts')
            ->whereNotNull('REFERENCED_COLUMN_NAME')
            ->get(['TABLE_NAME', 'COLUMN_NAME']);

        // Some imported databases no longer contain the original foreign key on
        // journal details, although account_id still references fn_accounts.
        $references->push((object) [
            'TABLE_NAME' => 'fn_constraint_details',
            'COLUMN_NAME' => 'account_id',
        ]);

        return $references->unique(
            fn ($reference) => $reference->TABLE_NAME . '.' . $reference->COLUMN_NAME
        )->values();
    }

    private function mostUsedTree(Collection $accounts): ?Account
    {
        return $accounts
            ->map(function (Account $account) {
                $ids = $this->descendantIds($account);
                $account->tree_usage = DB::table('fn_constraint_details')
                    ->whereIn('account_id', $ids)
                    ->count();

                return $account;
            })
            ->sortByDesc('tree_usage')
            ->first();
    }

    private function parentInSameBranch(Collection $parents, ?Account $reference): ?Account
    {
        if (!$reference) {
            return null;
        }

        $referenceAncestors = $this->ancestorIds($reference);
        array_pop($referenceAncestors);

        return $parents->first(function (Account $parent) use ($referenceAncestors) {
            return collect($this->ancestorIds($parent))->intersect($referenceAncestors)->isNotEmpty();
        });
    }

    private function descendantIds(Account $account): array
    {
        $ids = [$account->id];
        foreach ($account->children()->get() as $child) {
            $ids = array_merge($ids, $this->descendantIds($child));
        }

        return $ids;
    }

    private function ancestorIds(Account $account): array
    {
        $ids = [];
        $current = $account;
        while ($current->parent_id) {
            $ids[] = (int) $current->parent_id;
            $current = Account::query()->find($current->parent_id);
            if (!$current) {
                break;
            }
        }

        return $ids;
    }

    private function findPath(array $path): ?Account
    {
        $parent = null;
        foreach ($path as $names) {
            $query = Account::query();
            $parent
                ? $query->where('parent_id', $parent->id)
                : $query->whereNull('parent_id');

            $parent = $query->orderBy('code')->get()
                ->first(fn (Account $account) => $this->namesMatch($account->name, $names));
            if (!$parent) {
                return null;
            }
        }

        return $parent;
    }

    private function findDirectChild(Account $parent, array $names): ?Account
    {
        return $parent->children()->orderBy('code')->get()
            ->first(fn (Account $account) => $this->namesMatch($account->name, $names));
    }

    private function accountFromSetting(string $title): ?Account
    {
        $accountId = (int) ConfirmSetting::query()
            ->where('title', $title)
            ->value('value');

        return $accountId > 0 ? Account::query()->find($accountId) : null;
    }

    private function namesMatch(string $actual, array $names): bool
    {
        $normalized = $this->normalize($actual);

        return collect($names)->contains(fn (string $name) => $this->normalize($name) === $normalized);
    }

    private function formatNames(array $patterns, array $nationalities): array
    {
        return collect($nationalities)
            ->flatMap(fn (string $nationality) => collect($patterns)->map(
                fn (string $pattern) => sprintf($pattern, $nationality)
            ))
            ->unique()
            ->values()
            ->all();
    }

    private function nationalityName(Nationality $nationality): string
    {
        $ar = $nationality->translations->firstWhere('locale', 'ar');

        return trim((string) ($ar?->sub_title ?? $ar?->title ?? $nationality->title ?? ''));
    }

    private function nationalityAliases(string $name): array
    {
        $aliases = [$name];
        if (in_array($this->normalize($name), ['سيريلانكا', 'سريلانكا'], true)) {
            $aliases = array_merge($aliases, ['سيريلانكا', 'سريلانكا']);
        }

        return array_values(array_unique($aliases));
    }

    private function normalize(string $name): string
    {
        $name = trim((string) preg_replace('/\s+/u', ' ', $name));

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $name);
    }

    private function persistSetting(string $title, int $accountId): void
    {
        $setting = ConfirmSetting::query()->updateOrCreate(
            ['title' => $title],
            [
                'value' => $accountId,
                'created_by_id' => auth('admin')->id() ?: auth()->id() ?: 1,
            ]
        );

        ConfirmSetting::query()
            ->where('title', $title)
            ->whereKeyNot($setting->id)
            ->delete();
    }
}
