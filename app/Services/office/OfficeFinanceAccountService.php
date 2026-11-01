<?php

namespace App\Services\office;

use App\Models\office\Office;
use Illuminate\Support\Facades\DB;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\Models\Account;
use Nami\Finance\Services\AccountService;
use RuntimeException;

class OfficeFinanceAccountService
{
    public function __construct(protected AccountService $accountService)
    {
    }

    public function backfillExistingOffices(): void
    {
        Office::query()
            ->with(['account', 'translations'])
            ->lazyById()
            ->each(function (Office $office) {
                $this->ensureForOffice($office);
            });
    }

    public function ensureForOffice(Office $office): Account
    {
        return DB::transaction(function () use ($office) {
            $office = $office->fresh(['translations', 'account']) ?? $office->loadMissing(['translations', 'account']);
            $parent = $this->getOrCreateExternalAgentsParent();
            $desiredName = $this->resolveAccountName($office);

            // مكاتب الاستقدام قد تكون معرفة مسبقاً كموردين. في هذه الحالة يجب
            // استخدام حساب المورد نفسه، وعدم إنشاء حساب موازٍ تحت الوكلاء الخارجيين.
            $account = $this->findExistingSupplierAccount($office, $desiredName);

            if (!$account) {
                $account = $office->account;
            }

            if (!$account && filled($office->account_id)) {
                $account = Account::query()->find($office->account_id);
            }

            if (!$account) {
                $account = $this->findExistingAccount($parent, $office, $desiredName);
            }

            if (!$account) {
                $account = $this->createChildAccount($parent, $desiredName);
            } else {
                $this->syncAccountName($account, $desiredName);
            }

            if ((int) $office->account_id !== (int) $account->id) {
                $office->forceFill(['account_id' => $account->id])->save();
            }

            return $account->fresh();
        });
    }

    protected function getOrCreateExternalAgentsParent(): Account
    {
        return DB::transaction(function () {
            $parent = Account::query()
                ->where('code', \Nami\Finance\Support\AccountCodeAliases::current(221))
                ->lockForUpdate()
                ->first();

            if ($parent) {
                return $parent;
            }

            $shortLiabilities = Account::query()
                ->where('code', \Nami\Finance\Support\AccountCodeAliases::current(22))
                ->lockForUpdate()
                ->first();

            if (!$shortLiabilities) {
                $shortLiabilities = Account::query()
                    ->where('type', AccountTypesEnum::ShortLiabilities->value)
                    ->lockForUpdate()
                    ->first();
            }

            if (!$shortLiabilities) {
                $shortLiabilities = $this->createShortLiabilitiesParent();
            }

            return $this->accountService->store([
                'name' => 'الوكلاء الخارجيين',
                'operation_type' => $shortLiabilities->operation_type,
                'status' => $this->normalizeStatus($shortLiabilities->status),
                'statement_type' => $shortLiabilities->statement_type,
                'level' => ((int) $shortLiabilities->level + 1),
                'type' => AccountTypesEnum::NormalAccount->value,
                'parent_id' => $shortLiabilities->id,
                'code' => 221,
                'created_by_id' => auth('admin')->id() ?? auth()->id() ?? 1,
            ]);
        });
    }

    protected function createShortLiabilitiesParent(): Account
    {
        $liabilitiesParent = Account::query()
            ->where('code', 2)
            ->lockForUpdate()
            ->first();

        if (!$liabilitiesParent) {
            throw new RuntimeException('الحساب الرئيسي "الخصوم" غير موجود في الشجرة المالية.');
        }

        return $this->accountService->store([
            'name' => 'الخصوم قصيرة الأجل',
            'operation_type' => $liabilitiesParent->operation_type,
            'status' => $this->normalizeStatus($liabilitiesParent->status),
            'statement_type' => $liabilitiesParent->statement_type,
            'level' => ((int) $liabilitiesParent->level + 1),
            'type' => AccountTypesEnum::ShortLiabilities->value,
            'parent_id' => $liabilitiesParent->id,
            'code' => 22,
            'created_by_id' => auth('admin')->id() ?? auth()->id() ?? 1,
        ]);
    }

    protected function findExistingAccount(Account $parent, Office $office, string $desiredName): ?Account
    {
        $candidateNames = $this->accountCandidateNames($office, $desiredName);

        $accounts = Account::query()
            ->where('parent_id', $parent->id)
            ->whereIn('name', $candidateNames)
            ->orderBy('id')
            ->get();

        foreach ($candidateNames as $candidateName) {
            $matched = $accounts->firstWhere('name', $candidateName);
            if ($matched) {
                return $matched;
            }
        }

        return Account::query()
            ->where('parent_id', $parent->id)
            ->get()
            ->first(function (Account $account) use ($candidateNames) {
                $normalizedAccountName = $this->normalizeName($account->name);

                foreach ($candidateNames as $candidateName) {
                    if ($normalizedAccountName === $this->normalizeName($candidateName)) {
                        return true;
                    }
                }

                return false;
            });
    }

    protected function findExistingSupplierAccount(Office $office, string $desiredName): ?Account
    {
        $candidateNames = $this->accountCandidateNames($office, $desiredName);
        $accounts = Account::query()
            ->where('type', AccountTypesEnum::Supplier->value)
            ->whereIn('name', $candidateNames)
            ->orderBy('id')
            ->get();

        foreach ($candidateNames as $candidateName) {
            $matched = $accounts->firstWhere('name', $candidateName);
            if ($matched) {
                return $matched;
            }
        }

        return Account::query()
            ->where('type', AccountTypesEnum::Supplier->value)
            ->get()
            ->first(function (Account $account) use ($candidateNames) {
                foreach ($candidateNames as $candidateName) {
                    if ($this->normalizeName($account->name) === $this->normalizeName($candidateName)) {
                        return true;
                    }
                }

                return false;
            });
    }

    protected function accountCandidateNames(Office $office, string $desiredName): array
    {
        return array_values(array_unique(array_filter([
            $desiredName,
            trim((string) optional($office->translate('ar'))->title),
            trim((string) optional($office->translate('en'))->title),
            trim((string) $office->title),
        ])));
    }

    protected function createChildAccount(Account $parent, string $name): Account
    {
        $childType = $this->accountService->getChildType($parent);

        return $this->accountService->store([
            'name' => $name,
            'operation_type' => $parent->operation_type,
            'status' => $this->normalizeStatus($parent->status),
            'statement_type' => $parent->statement_type,
            'level' => ((int) $parent->level + 1),
            'type' => $childType instanceof \BackedEnum ? $childType->value : $childType,
            'parent_id' => $parent->id,
            'code' => $this->accountService->getChildCode(
                $parent->id,
                $this->accountService->childPluckCode($parent->id),
                $this->accountService->parentPluckCode()
            ),
            'created_by_id' => auth('admin')->id() ?? auth()->id() ?? 1,
        ]);
    }

    protected function syncAccountName(Account $account, string $desiredName): void
    {
        if (trim((string) $account->name) === $desiredName) {
            return;
        }

        $this->accountService->update($account->id, [
            'name' => $desiredName,
        ]);
    }

    protected function resolveAccountName(Office $office): string
    {
        $name = trim((string) optional($office->translate('ar'))->title);

        if ($name !== '') {
            return $name;
        }

        $name = trim((string) $office->title);

        if ($name !== '') {
            return $name;
        }

        $name = trim((string) optional($office->translate('en'))->title);

        if ($name !== '') {
            return $name;
        }

        return 'وكيل خارجي #' . $office->id;
    }

    protected function normalizeStatus(mixed $status): string
    {
        return $status instanceof \BackedEnum
            ? (string) $status->value
            : (string) $status;
    }

    protected function normalizeName(string $name): string
    {
        $normalized = trim(preg_replace('/\s+/u', ' ', $name));

        return str_replace(['أ', 'إ', 'آ', 'ة', 'ى'], ['ا', 'ا', 'ا', 'ه', 'ي'], $normalized);
    }
}
