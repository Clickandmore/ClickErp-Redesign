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
        return $this->accountService->update($box->account_id,
            [
            "name" => $data["title"],
        ]);
    }

    public function store($data)
    {
        $box = parent::store($data);
        $parent = $this->getParent();
        $account =  $this->insertAccount($parent,$data["title"]);;
        $box->update(["account_id" => $account->id]);
        return $box;
    }


    public function update($id, $data)
    {
        $box = $this->find($id);
        parent::update($id, $data);
        $this->updateAccount($box, $data);
        return $box;
    }

}
