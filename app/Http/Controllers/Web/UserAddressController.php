<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\UserAddressAction;
use App\Http\Actions\ingaz\UserAddressAction as objAction;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public $postData = ["address","title","name","phone","notes"];
    public $folderPath = "web.ingaz.user-addresses";

    public $mainRoute = "UserAddresss";


    public function index(Request $request,UserAddressAction $userAddressAction)
    {
        if ($request->ajax()) {
            $data['addresses'] = $userAddressAction->getWhere(['user_id'=>getGuardedUser('id')]);
            $returnHTML = view($this->folderPath . ".index",$data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, objAction $action)
    {
        $data = $request->only($this->postData);
        $data['address'] = $action->storeUserAddress($data);
        return jsonSuccess($data);
    }
}
