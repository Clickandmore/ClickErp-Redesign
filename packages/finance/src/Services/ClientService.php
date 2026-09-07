<?php

namespace Nami\Finance\Services;

use App\Support\BranchReportFilter;
use App\Models\ingaz\User as CoreClient;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
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

    /**
     * The operational client record lives in users. fn_clients is kept for
     * legacy sales records, but it must not be used as the finance client list.
     */
    public function getFinancialClientsDataTable(array $filters = [])
    {
        $balances = $this->clientAccountBalancesQuery();

        return CoreClient::query()
            ->leftJoin('fn_accounts as client_account', 'client_account.id', '=', 'users.account_id')
            ->leftJoin('fn_client_categories as client_category', 'client_category.id', '=', 'users.client_category_id')
            ->leftJoinSub($balances, 'client_balance', function ($join) {
                $join->on('client_balance.account_id', '=', 'users.account_id');
            })
            ->select([
                'users.id',
                'users.name',
                'users.phone',
                'users.phone2',
                'users.email',
                'users.city',
                'users.address',
                'users.national_num',
                'users.tax_number',
                'users.license_number',
                'users.client_type',
                'users.client_category_id',
                'users.account_id',
                'users.is_blocked',
                'users.created_at',
                'client_account.code as account_code',
                'client_account.name as account_name',
                'client_account.status as account_status',
                'client_account.type as account_type',
                'client_category.name as category_name',
            ])
            ->selectRaw('COALESCE(client_balance.total_debtor, 0) as total_debtor')
            ->selectRaw('COALESCE(client_balance.total_creditor, 0) as total_creditor')
            ->selectRaw('(COALESCE(client_balance.total_debtor, 0) - COALESCE(client_balance.total_creditor, 0)) as current_balance')
            ->selectRaw('COALESCE(client_balance.movement_count, 0) as movement_count')
            ->addSelect('client_balance.last_movement_at')
            ->when(!empty($filters['category_id']), function ($query) use ($filters) {
                $query->where('users.client_category_id', $filters['category_id']);
            })
            ->when(($filters['account_link'] ?? '') === 'linked', function ($query) {
                $query->whereNotNull('users.account_id')->whereNotNull('client_account.id');
            })
            ->when(($filters['account_link'] ?? '') === 'unlinked', function ($query) {
                $query->where(function ($accountQuery) {
                    $accountQuery->whereNull('users.account_id')->orWhereNull('client_account.id');
                });
            })
            ->when(($filters['client_status'] ?? '') === 'active', function ($query) {
                $query->where('users.is_blocked', 0);
            })
            ->when(($filters['client_status'] ?? '') === 'blocked', function ($query) {
                $query->where('users.is_blocked', 1);
            })
            ->when(($filters['balance_state'] ?? '') === 'debtor', function ($query) {
                $query->whereRaw('(COALESCE(client_balance.total_debtor, 0) - COALESCE(client_balance.total_creditor, 0)) >= 0.01');
            })
            ->when(($filters['balance_state'] ?? '') === 'creditor', function ($query) {
                $query->whereRaw('(COALESCE(client_balance.total_creditor, 0) - COALESCE(client_balance.total_debtor, 0)) >= 0.01');
            })
            ->when(($filters['balance_state'] ?? '') === 'balanced', function ($query) {
                $query->whereRaw('ABS(COALESCE(client_balance.total_debtor, 0) - COALESCE(client_balance.total_creditor, 0)) < 0.01');
            })
            ->orderByDesc('users.id');
    }

    public function getFinancialClientsSummary(): array
    {
        $balances = $this->clientAccountBalancesQuery();

        $clientCounts = DB::table('users')
            ->leftJoin('fn_accounts as client_account', 'client_account.id', '=', 'users.account_id')
            ->selectRaw('COUNT(users.id) as clients_count')
            ->selectRaw('SUM(CASE WHEN users.account_id IS NOT NULL AND client_account.id IS NOT NULL THEN 1 ELSE 0 END) as linked_count')
            ->selectRaw('SUM(CASE WHEN users.account_id IS NULL OR client_account.id IS NULL THEN 1 ELSE 0 END) as unlinked_count')
            ->first();

        // Sum each account once even if legacy data links two clients to the
        // same account. The client list still shows both records for correction.
        $accountTotals = DB::query()
            ->fromSub($balances, 'client_balance')
            ->whereIn('client_balance.account_id', DB::table('users')
                ->whereNotNull('account_id')
                ->select('account_id')
                ->distinct())
            ->selectRaw('COALESCE(SUM(client_balance.total_debtor), 0) as total_debtor')
            ->selectRaw('COALESCE(SUM(client_balance.total_creditor), 0) as total_creditor')
            ->first();

        $totalDebtor = round((float) ($accountTotals->total_debtor ?? 0), 2);
        $totalCreditor = round((float) ($accountTotals->total_creditor ?? 0), 2);

        return [
            'clients_count' => (int) ($clientCounts->clients_count ?? 0),
            'linked_count' => (int) ($clientCounts->linked_count ?? 0),
            'unlinked_count' => (int) ($clientCounts->unlinked_count ?? 0),
            'total_debtor' => $totalDebtor,
            'total_creditor' => $totalCreditor,
            'net_balance' => round($totalDebtor - $totalCreditor, 2),
        ];
    }

    private function clientAccountBalancesQuery(): Builder
    {
        return BranchReportFilter::apply(DB::table('fn_constraint_details as detail'), 'detail')
            ->join('fn_constraints as constraint', 'constraint.id', '=', 'detail.constraint_id')
            ->whereNull('detail.deleted_at')
            ->whereNull('constraint.deleted_at')
            ->groupBy('detail.account_id')
            ->select('detail.account_id')
            ->selectRaw('SUM(detail.debtor_value) as total_debtor')
            ->selectRaw('SUM(detail.creditor_value) as total_creditor')
            ->selectRaw('COUNT(detail.id) as movement_count')
            ->selectRaw('MAX(detail.date) as last_movement_at');
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
