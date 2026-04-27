<?php

namespace Nami\Finance\Http\Controllers;


use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\Bond\StoreBondConfirmRequest as StoreRequest;
use Nami\Finance\Http\Requests\Bond\UpdateBondConfirmRequest as UpdateRequest;
use Nami\Finance\Services\BondService as ObjService;

class BondConfirmController extends Controller
{
    public $folderPath = "";
    public $postData = ["bond_id"];
    public $mainRoute = "confirm-bond";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(StoreRequest $request, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
//        return $service->confirmBond($dataInsert["bond_id"]);
        $data = $service->confirmBond($dataInsert["bond_id"]);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        /*if ($request->ajax()) {
            $obj = $service->find($id) ;
            $returnHTML = view($this->folderPath . ".edit")->with([
                "bond_payment_type" => $service->getBondPaymentTypies(),
                "accounts" => $service->getAccounts(),
                "obj" => $obj ,
                "edit_payment_type_view"=>$service->getEditPaymentTypeView($obj->payment_type,"sarf"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }*/
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(UpdateRequest $request, $id, ObjService $service)
    {
        /*$dataInsert = $request->only($this->postData);
        $data = $service->updateBond($id, $dataInsert);
        return jsonSuccess($data);*/
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
       /* $service->delete($id);
        return jsonSuccess();*/
    }
}
