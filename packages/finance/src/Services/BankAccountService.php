<?php

namespace Nami\Finance\Services;

use Nami\Finance\MainService;
use Nami\Finance\Models\Bank;
use Nami\Finance\Models\BankAccount;

class BankAccountService extends MainService
{
    public function __construct(BankAccount $model)
    {
        $this->model = $model;
    }

    public function getBanks()
    {
        return Bank::get();
    }

    public function updateAccountName($account_id, $data, $accountAction) {
        $account = $accountAction->find($account_id);
        if (isset($account)) {
            $accountAction->update($account_id, ['name' => $data['name']]);
        }
    }
    public function getBankDataTable(): \Illuminate\Database\Eloquent\Builder
    {
        return $this->model->query()
            ->when(request('bank_id'), function ($query) {
                return $query->where('bank_id', request('bank_id'));
            })
            ->latest();
    }
}
