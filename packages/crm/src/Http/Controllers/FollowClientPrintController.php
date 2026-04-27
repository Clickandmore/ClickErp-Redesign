<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\FollowClientService as ObjService;
use Nami\Crm\Http\Requests\FollowClientRequest;
use Nami\Crm\Models\FollowClientCategory;
use Illuminate\Http\Request;
use Nami\Crm\Models\FollowClient as ObjModel;
use App\Models\Nami\Admin as adminModel;
use Yajra\DataTables\DataTables;


class FollowClientPrintController extends Controller
{
    public $folderPath = "crm::crm.follow_clients";
    public $postData = ['client_id', 'admin_id', 'follow_clients_category_id', 'date', 'content', 'notes', 'result'];
    public $mainRoute = "crm.follow-clients";



    /**
     * @param $id
     * @param Request $request
     * @param ObjService $service
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\View\View
     */
    public function print($id, Request $request, ObjService $service)
    {
        $obj = $service->find($id);
        return view($this->folderPath . ".print")->with([
            'clients' => $service->getClients(),
            'categories' => FollowClientCategory::all(),
            'users' => adminModel::all(),
            'bladeTitle' => __("crm.follow-clients"),
            "obj" => $obj,
        ]);

    }
}
