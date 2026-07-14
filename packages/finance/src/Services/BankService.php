<?php

namespace Nami\Finance\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Account;

class BankService extends MainService
{
    public function __construct(Account $model, protected AccountService $accountService)
    {
        $this->model = $model;
    }

    public function getDataTable()
    {
        $parent = $this->getBanksParentOrFail();

        return $this->model->newQuery()
            ->where('parent_id', $parent->id)
            ->whereIn('type', [
                AccountTypesEnum::BankName->value,
                AccountTypesEnum::Bank->value,
            ])
            ->withCount([
                'children as accounts_count' => fn ($query) => $query->where('type', AccountTypesEnum::Bank->value),
            ])
            ->orderBy('code');
    }

    public function findBank(int $id): Account
    {
        $parent = $this->getBanksParentOrFail();

        $bank = $this->model->newQuery()
            ->where('parent_id', $parent->id)
            ->whereIn('type', [
                AccountTypesEnum::BankName->value,
                AccountTypesEnum::Bank->value,
            ])
            ->with(['children' => fn ($query) => $query
                ->where('type', AccountTypesEnum::Bank->value)
                ->orderBy('code')])
            ->findOrFail($id);

        if ($bank->type === AccountTypesEnum::Bank) {
            $bank->setRelation('children', new Collection([$bank]));
        }

        return $bank;
    }

    public function store($data)
    {
        return DB::transaction(function () use ($data) {
            $parent = $this->getBanksParentOrFail();
            $bank = $this->createAccount($parent, $data['name'], AccountTypesEnum::BankName);

            foreach ($data['accounts'] as $accountData) {
                $this->assertUniqueBankDetails($accountData);
                $this->createAccount($bank, $accountData['name'], AccountTypesEnum::Bank, $accountData);
            }

            return $bank->load('children');
        });
    }

    public function updateBank(int $id, array $data): Account
    {
        return DB::transaction(function () use ($id, $data) {
            $bank = $this->findBank($id);

            if ($bank->type === AccountTypesEnum::Bank) {
                $bank = $this->convertDirectAccountToBank($bank, $data['name']);
            } else {
                $bank->update(['name' => $data['name']]);
            }

            $existingAccounts = $bank->children()
                ->where('type', AccountTypesEnum::Bank->value)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');
            $submittedIds = collect($data['accounts'])->pluck('id')->filter()->map(fn ($id) => (int) $id);

            foreach ($existingAccounts->whereNotIn('id', $submittedIds) as $removedAccount) {
                $this->deleteAccountOrFail($removedAccount);
            }

            foreach ($data['accounts'] as $accountData) {
                $accountId = isset($accountData['id']) ? (int) $accountData['id'] : null;
                $this->assertUniqueBankDetails($accountData, $accountId);

                if ($accountId) {
                    $account = $existingAccounts->get($accountId);

                    if (!$account) {
                        throw ValidationException::withMessages([
                            'accounts' => __('finance::finance.invalid_bank_account'),
                        ]);
                    }

                    $account->update($this->bankAccountAttributes($accountData));
                    continue;
                }

                $this->createAccount($bank, $accountData['name'], AccountTypesEnum::Bank, $accountData);
            }

            return $bank->fresh()->load(['children' => fn ($query) => $query->orderBy('code')]);
        });
    }

    public function deleteBank(int $id): void
    {
        DB::transaction(function () use ($id) {
            $bank = $this->findBank($id);

            if ($bank->type === AccountTypesEnum::Bank) {
                $this->deleteAccountOrFail($bank);
                return;
            }

            $accounts = $bank->children()
                ->where('type', AccountTypesEnum::Bank->value)
                ->lockForUpdate()
                ->get();

            foreach ($accounts as $account) {
                $this->deleteAccountOrFail($account);
            }

            if ($bank->children()->exists()) {
                throw ValidationException::withMessages([
                    'bank' => __('finance::finance.bank_has_children'),
                ]);
            }

            $bank->delete();
        });
    }

    private function convertDirectAccountToBank(Account $account, string $bankName): Account
    {
        $parent = $this->getBanksParentOrFail();
        $bank = $this->createAccount($parent, $bankName, AccountTypesEnum::BankName);
        $this->accountService->updateAccount($account->id, [
            'name' => $account->name,
            'status' => $account->status->value,
            'parent_id' => $bank->id,
            'type' => AccountTypesEnum::Bank->value,
            'operation_type' => $account->operation_type,
        ], 0, null);

        return $bank;
    }

    private function createAccount(
        Account $parent,
        string $name,
        AccountTypesEnum $type,
        array $details = []
    ): Account {
        return $this->accountService->storeAccount(array_merge([
            'name' => $name,
            'status' => $parent->status->value,
            'parent_id' => $parent->id,
            'type' => $type->value,
            'operation_type' => $parent->operation_type,
            'created_by_id' => auth('admin')->id() ?? 1,
        ], $type === AccountTypesEnum::Bank ? $this->bankAccountAttributes($details) : []));
    }

    private function bankAccountAttributes(array $data): array
    {
        return [
            'name' => $data['name'],
            'bank_number' => !empty($data['bank_number']) ? trim($data['bank_number']) : null,
            'iban' => !empty($data['iban']) ? strtoupper(str_replace(' ', '', $data['iban'])) : null,
        ];
    }

    private function assertUniqueBankDetails(array $data, ?int $ignoreId = null): void
    {
        foreach (['bank_number', 'iban'] as $field) {
            $value = $data[$field] ?? null;

            if (!$value) {
                continue;
            }

            if ($field === 'iban') {
                $value = strtoupper(str_replace(' ', '', $value));

                if (strlen($value) > 34) {
                    throw ValidationException::withMessages([
                        'accounts.*.iban' => __('finance::finance.invalid_iban_length'),
                    ]);
                }
            } else {
                $value = trim($value);
            }

            $exists = $this->model->newQuery()
                ->where($field, $value)
                ->when($ignoreId, fn ($query) => $query->where('id', '<>', $ignoreId))
                ->exists();

            if ($exists) {
                throw ValidationException::withMessages([
                    "accounts.*.$field" => __('finance::finance.duplicate_bank_detail'),
                ]);
            }
        }
    }

    private function deleteAccountOrFail(Account $account): void
    {
        if ($account->children()->exists()) {
            throw ValidationException::withMessages([
                'bank' => __('finance::finance.bank_has_children'),
            ]);
        }

        if ($account->constraintDetails()->exists()) {
            throw ValidationException::withMessages([
                'bank' => __('finance::finance.bank_has_constraints'),
            ]);
        }

        $account->delete();
    }

    private function getBanksParent(): ?Account
    {
        return $this->accountService->findByType(AccountTypesEnum::Banks->value);
    }

    private function getBanksParentOrFail(): Account
    {
        $parent = $this->getBanksParent();

        if (!$parent) {
            throw ValidationException::withMessages([
                'name' => __('finance::finance.banks_parent_not_found'),
            ]);
        }

        return $parent;
    }
}
