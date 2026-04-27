<?php

namespace Nami\Finance\Services;

use Nami\Finance\Enums\AccountTypesEnum;
use Nami\Finance\MainService;
use Nami\Finance\Models\Client;
use Nami\Finance\Models\ClientAttachment;

class ClientService extends MainService
{
    public function __construct(Client $model,protected AccountService $accountService,private ClientCategoryService $clientCategoryService, private ClientImageService $clientimageService)
    {
        $this->model = $model;
        $this->fileFolder = 'Clients/';
        $this->files = ['image'];
    }

    public function getParent()
    {
        return $this->accountService->findByType(AccountTypesEnum::Clients);
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
    public function updateAccount($item, $data)
    {
        return $this->accountService->update($item->account_id,
            [
            "name" => $data["name"],
        ]);
    }


    public function customStore($data)
    {
        $parent = $this->getParent();
        $account =  $this->insertAccount($parent,$data["name"]);
        $data["account_id"] = $account->id;
        $data["code"] = $account->code;
        $client = parent::store($data);
        if(isset($data['images']) && count($data['images']) > 0){
            $this->clientimageService->storeImages($client->id, $data['images']);
        }
        if (isset($data['file'])) {
            $this->storeCustomerFiles($client->id, $data);
        }
        return $client;
    }


    public function customUpdate($id, $data)
    {
        $obj = $this->find($id);
        parent::update($id, $data);
        $this->updateAccount($obj, $data);
        if(isset($data['images']) && count($data['images']) > 0){
            $this->clientimageService->storeImages($id, $data['images']);
        }
        return $obj;
    }

    public function createData($mainRoute)
    {
        return [
            'storeRoute' => route($mainRoute.'.store'),
            'client_categories' => $this->clientCategoryService->get()
        ];
    }


   public function showData($id)
   {
       return [
           'obj' => $this->find($id),
           'client_categories' => $this->clientCategoryService->get()
       ];
   }

    public function editData($id, $mainRoute)
    {
        return [
            'obj' => $this->find($id),
            'updateRoute' => route($mainRoute.'.update', $id),
            'client_categories' => $this->clientCategoryService->get()
        ];
    }

    public function getAccountByID($client_id)
    {
        return $this->find($client_id)->account;
    }

    public function deleteClient($id)
    {
        $this->clientimageService->deleteFiles($id);
        $this->deleteWithFile($id ,'image');
        return true;
    }
    public function getClientDataTable()
    {
        return $this->model->query()
            ->when(request()->category_id,function($q){
                $q->where('client_category_id',request()->category_id);
            })
            ->latest();
    }
    public function storeCustomerFiles($id, $data)
    {
        foreach ($data['file'] as $file) {
            ClientAttachment::create([
                'client_id' => $id ,
                'file'=> uploadFile($file,'clients-attachments'),
            ]);
        }


    }

}
