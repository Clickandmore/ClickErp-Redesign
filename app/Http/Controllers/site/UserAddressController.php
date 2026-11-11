<?php

namespace App\Http\Controllers\site;

use App\Http\Actions\ingaz\UserAddressAction;
use App\Http\Actions\ingaz\UserAddressAction as objAction;
use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;

class UserAddressController extends Controller
{
    public $postData = ["address", "title", "name", "phone", "notes"];
    public $folderPath = "site.pages.user-addresses";

    public $mainRoute = "user-addresses";


    public function index(Request $request, UserAddressAction $userAddressAction)
    {
        if ($request->ajax()) {
            $data['addresses'] = $userAddressAction->getWhere(['user_id' => getGuardedUser('id')]);
            $returnHTML = view($this->folderPath . ".index", $data)->render();
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

    public function edit(Request $request, $id, objAction $action)
    {
        $obj = $this->ownedAddress($action, $id);
        return response()->json([
            'data' => $obj
        ]);

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
        $action->storeUserAddress($data);
        $addresses = $action->getWhere(['user_id' => getGuardedUser()->id]);
        $html = view($this->folderPath . ".render_user_address", compact('addresses'))->render();
        return jsonSuccess(['html' => $html], __("ingaz.user_address_success"), 200);
    }

    public function update(Request $request, $id, objAction $action)
    {
        $data = $request->only($this->postData);
        $this->ownedAddress($action, $id);
        $action->update($id, $data);
        $addresses = $action->getWhere(['user_id' => getGuardedUser()->id]);
        $html = view($this->folderPath . ".render_user_address", compact('addresses'))->render();
        return jsonSuccess(['html' => $html], __("ingaz.user_address_success"), 200);
    }

    public function destroy(objAction $action, $id)
    {
        $obj = $this->ownedAddress($action, $id);
        $obj->delete();
        $addresses = $action->getWhere(['user_id' => getGuardedUser()->id]);
        $html = view($this->folderPath . ".render_user_address", compact('addresses'))->render();
        return jsonSuccess(['html' => $html], __("ingaz.user_address_success"), 200);
    }

    // العنوان يخص العميل المسجّل دخوله فقط؛ كان يمكن تعديل/حذف عنوان أي عميل برقمه
    private function ownedAddress(objAction $action, $id)
    {
        $address = $action->getWhereFirst(['id' => $id, 'user_id' => getGuardedUser('id')]);
        abort_unless($address, 404);

        return $address;
    }
}
