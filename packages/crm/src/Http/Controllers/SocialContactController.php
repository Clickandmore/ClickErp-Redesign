<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\SocialContactService as ObjService;
use Nami\Crm\Http\Requests\SocialContactRequest as ObjRequest;
use Nami\Crm\Models\Client;
use Illuminate\Http\Request;
use Nami\Crm\Models\SocialContact as ObjModel;
use Yajra\DataTables\DataTables;


class SocialContactController extends Controller
{
    public $folderPath = "crm::crm.social_contacts";
    public $postData = ["title", "is_shown"];
    public $mainRoute = "crm.social_contacts";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()

                ->editColumn('is_shown', function ($row) {
                    if ($row->is_shown == 1) {
                        $is_shown = "<button class='btn btn-success update-status' data-id='" . $row->id . "'>" . __("crm::crm.show") . "</button>";
                    } else {
                        $is_shown = "<button class='btn btn-danger update-status' data-id='" . $row->id . "'>" . __("crm::crm.hidden") . "</button>";
                    }
                    return $is_shown;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.social_contacts");
        $data["addButtonText"] = __("crm::crm.social_contacts");
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, ObjService $service)
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
     * @return \Illuminate\Http\Response
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->store($dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id, ObjService $service)
    {
        $crm = $service->find($id);
        $crm->is_shown = !$crm->is_shown;
        $crm->save();
        return response()->json(['success' => true]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id)
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(ObjRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->only($this->postData);
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, ObjService $service)
    {
        $obj = $service->find($id);
        $clientCount = Client::where('social_contact_id', $obj->id)->count();
        if ($clientCount > 0) {
            return jsonValid(['error' => [__("crm.sorry,can't delete this item")]], '');
        } else {
            $service->delete($id);
            return jsonSuccess();
        }
    }
}
