<?php

namespace Nami\Finance\Services;

use App\Http\Actions\finance\AccountAction;
use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Box;


class BoxService extends MainService
{
    public function __construct(Box $model,protected AccountService $accountService)
    {
        $this->model = $model;
        $this->fileFolder = 'boxs/';
    }

    public function getParent()
    {
        return $this->accountService->findByType(AccountTypesEnum::Boxs);
    }

    public function getChildType($parent)
    {
        return  $this->accountService->getChildType($parent);
    }
    public function getChildCode($parent)
    {
        $parentPluckCode = $this->accountService->parentPluckCode();
        $childPluckCode = $this->accountService->childPluckCode($parent->id);
        return  $this->accountService->getChildCode($parent->id,$childPluckCode,$parentPluckCode);
    }

    public function insertAccount($parent,$name)
    {
        return $this->accountService->store([
            "name" => $name,
            "status" => $parent->status,
            "level" => ($parent->level + 1),
            "type" => $this->getChildType($parent),
            "parent_id" => $parent->id,
            "code" => $this->getChildCode($parent),
        ]);
    }
    public function updateAccount($box, $data)
    {
        return $this->accountService->updateAccountGuideDetails($box->account_id,
            [
            "name" => $data["title"],
        ]);
    }

    public function store($data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($data) {
            $scopePresent = !empty($data['account_branch_scope_present']);
            $branchIds = $data['branch_ids'] ?? [];
            unset($data['account_branch_scope_present'], $data['branch_ids'], $data['account_id']);
            $account = $this->insertAccount($this->getParent(), $data['title']);
            $box = parent::store($data + ['account_id' => $account->id]);
            $this->syncBranches($account, $scopePresent, $branchIds);
            return $box;
        });
    }


    public function update($id, $data)
    {
        return \Illuminate\Support\Facades\DB::transaction(function () use ($id, $data) {
            $scopePresent = !empty($data['account_branch_scope_present']);
            $branchIds = $data['branch_ids'] ?? [];
            unset($data['account_branch_scope_present'], $data['branch_ids'], $data['account_id']);
            $box = $this->find($id);
            parent::update($id, $data);
            if ($box->account) {
                $this->updateAccount($box, $data);
            } else {
                $account = $this->insertAccount($this->getParent(), $data['title']);
                $box->update(['account_id' => $account->id]);
            }
            $this->syncBranches($box->fresh()->account, $scopePresent, $branchIds);
            return $box->fresh();
        });
    }

    private function syncBranches($account, bool $scopePresent, array $branchIds): void
    {
        if ($scopePresent && \App\Support\SystemModuleManager::isEnabled('branches')
            && \App\Support\BranchSharingSettings::accountsPerBranch()) {
            $account->branches()->sync($branchIds);
        }
    }

}
