<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\ClientService as ObjAction;
use Nami\Crm\Http\Requests\ClientRequest as ObjRequest;
use Nami\Crm\Models\Client;
use Illuminate\Http\Request;
use Nami\Crm\Models\Client as ObjModel;
use Yajra\DataTables\DataTables;


class ConfirmClientController extends Controller
{
    public $folderPath = "erp.crm.clients";
    public $postData = ['name', 'email', 'client_id'];
    public $mainRoute = "crm.confirm-clients";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, ObjAction $action)
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjAction $action)
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(ObjRequest $request, ObjAction $action)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjAction $action)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjAction $action)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".confirm")->with([
                "obj" => $action->find($id),
                "cities" => $action->getCities(),
                "areas" => $action->getAreas(),
                "social_contacts" => $action->getSocialContacts(),
                "users" => $action->getUsers(),
                "categories" => $action->getCategories(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(ObjRequest $request, $id, ObjAction $action)
    {
        $dataInsert = $request->only($this->postData);
        $client = Client::find($id);
        $request['whatsapp'] = $client->whatsapp;
        $request['notes'] = $client->notes;
        $request['vat_number'] = $client->vat_number;
        $dataInsert['client_id'] = $client->id;
        $obj = $action->find($id);
        $data = $obj->update($dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }
}
