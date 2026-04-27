<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Bank;

class BankService extends MainService
{
    public function __construct(Bank $model,protected AccountService $accountAction)
    {
        $this->model = $model;
        $this->fileFolder = 'bank/';
        $this->files = ['image'];
    }

    public function store($data)
    {

        $bank = parent::store($data);

        $parent = $this->accountAction->findByType(AccountTypesEnum::Banks);
        $account =  $this->dalelAccount($data['name:ar'], $parent);
        parent::getWhereColumn(['id' => $bank->id])->update(['account_id' => $account->id]);
        return ['bank' => $bank, 'account' => $account];
    }

    public function storeBankAccount($bank, $request,$parent_id) {
        $bank->bank_accounts()->delete();
        $parent = $this->accountAction->find($parent_id);
        if ($parent->children()->exists()) {
            $parent->children()->delete();
        }

        foreach ($request->accounts as $account) {
            $inserted = $this->dalelAccount($account['name'], $parent, AccountTypesEnum::Bank->value);
            $bank->bank_accounts()->create(array_merge($account, [
                'account_id' => $inserted->id,
                'created_by_id' => auth('admin')->id() ?? 1,
            ]));

        }
        return true;
    }

    public function updateAccountName($account_id, $data) {
        $account = $this->accountAction->find($account_id);
        if (isset($account)) {
            $this->accountAction->update($account_id, ['name' => $data['name:ar']]);
        }
    }

    public function dalelAccount($name,$parent,$type = 111)
    {
        $accountAction = $this->accountAction;
        $parentPluckCode = $this->accountAction->parentPluckCode();
        $childPluckCode = $accountAction->childPluckCode($parent->id);

        $account = $accountAction->store([
            "name" => $name,
            "status" => @$parent->status,
            "level" => @$parent->level ? ($parent->level + 1) :1,
            "type" => $type,
            "parent_id" => $parent->id,
            "code" => $accountAction->getChildCode($parent->id, $childPluckCode, $parentPluckCode),
        ]);
        return $account;
    }
}
