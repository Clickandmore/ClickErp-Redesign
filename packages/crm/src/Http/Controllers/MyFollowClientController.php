<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Nami\Admin;
use Nami\Crm\Services\FollowClientService as ObjService;
use Nami\Crm\Http\Requests\FollowClientRequest;
use Nami\Crm\Models\FollowClientCategory;
//use App\Models\User;
use Illuminate\Http\Request;
use Nami\Crm\Models\FollowClient as ObjModel;
use Yajra\DataTables\DataTables;


class MyFollowClientController extends Controller
{
    public $folderPath = "crm::crm.my_followups";
    public $postData = ['client_id', 'user_id', 'follow_clients_category_id', 'date', 'content', 'notes', 'result'];
    public $mainRoute = "crm.my-followups";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Foundation\Application|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::latest()->whereHas('user', function ($query) {
                $query->where('id', auth('admin')->id());
            })->get();

            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('client_id', function ($row) {
                    return $row->client->name;
                })
                ->editColumn('user', function ($row) {
                    return $row->user->name;
                })
                ->editColumn('category', function ($row) {
                    return $row->category->title;
                })
                ->filter(function ($instance) use ($request) {

                    if ($request->get('client_id')) {
                        $instance->where('client_id', $request->get('client_id'));
                    }
                    if ($request->get('user_id')) {
                        $instance->where('user_id', $request->get('user_id'));
                    }
                    if ($request->get('from_date')) {
                        $instance->where('date', '<=', $request->get('from_date'));
                    }
                    if ($request->get('to_date')) {
                        $instance->where('date', '>=', $request->get('to_date'));
                    }
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    $showButton = editButton(route($this->mainRoute . ".show", $row->id), $row->name, 'eye', '', __('crm.show'), 'success');

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    return $editButton . " " . $showButton . " " . $deleteButton;
                })->rawColumns(['actions', 'client_id'])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.my-followups");
        $data["addButtonText"] = __("crm::crm.my-followups");
        $data["clients"] = $service->getClients();
//        $data["users"] = User::all();
        // $data["modalType"] = "";
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
            $returnHTML = view($this->folderPath . ".create")->with([
                'clients' => $service->getClients(),
                'categories' => FollowClientCategory::all(),
//                'users' => User::whereHas('employee')->get(),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function store(FollowClientRequest $request, ObjService $service)
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
    public function show(Request $request, $id, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".show")->with([
                "obj" => $service->find($id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
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
                "obj" => $service->find($id),
                'clients' => $service->getClients(),
                'categories' => FollowClientCategory::all(),
                'users' => Admin::get(),
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
    public function update(FollowClientRequest $request, $id, ObjService $service)
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
        $service->delete($id);
        return jsonSuccess();
    }

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
//            'users' => User::all(),
            'bladeTitle' => __("crm::crm.follow-clients"),
            "obj" => $obj,
        ]);

    }
}
