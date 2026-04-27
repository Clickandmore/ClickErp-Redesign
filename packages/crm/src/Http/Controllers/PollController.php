<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\PollService as ObjService;
use Nami\Crm\Http\Requests\PollRequest as ObjRequest;
use Nami\Crm\Models\Client;
use Nami\Crm\Models\User;
use Illuminate\Http\Request;
use Nami\Crm\Models\Poll as ObjModel;
use Yajra\DataTables\DataTables;


class PollController extends Controller
{
    public $folderPath = "crm::crm.polls";
    public $mainRoute = "crm.polls";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('show', function ($row) {
                    return viewButton(route("start-poll-show", ['poll_id' => $row->id]), $row->name, 'face-smile');

                })
                ->addColumn('clients', function ($row) {
                    return viewButton(route("crm.poll-clients.index", ['poll_id' => $row->id]), $row->name, 'users');
                })
                ->addColumn('send_poll_clients', function ($row) {
                    return addExtraButton(route("crm.sendPoll.index", ['poll_id' => $row->id]), __("crm::crm.sendPoll"));
                })
                ->addColumn('questions', function ($row) {
                    $viewButton = '<a  class="editButtonStyle "
                       href="' . route('crm.questions.index', ['poll_id' => $row->id]) . '">
                                     <i class="fa fa-eye"></i>
                                </a>';

                    $addButton = addButtonInTable(route("crm.questions.create", ['poll_id' => $row->id]), $row->name, 'question', __("crm.questions"));

                    return $addButton . " " . $viewButton;
                })
                ->addColumn('actions', function ($row) {

                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("crm::crm.polls");
        $data["addButtonText"] = __("crm::crm.polls");
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function sendPollClient(Request $request)
    {
        if ($request->ajax()) {
            $clients = Client::where('email', '!=', null)->get();
            $poll_id = $request->poll_id;
            $returnHTML = view($this->folderPath . ".send_poll_client", compact('poll_id', 'clients'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function sendPollUser(Request $request)
    {
        if ($request->ajax()) {
            $users = User::where('email', '!=', null)->whereIn('user_type', ['P', 'C', 'company'])->get();
            $poll_id = $request->poll_id;
            $returnHTML = view($this->folderPath . ".send_poll_user", compact('poll_id', 'users'))->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->store($dataInsert, $request);
        return jsonSuccess($data);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show(Request $request, $id, ObjService $service)
    {
        if ($request->ajax()) {
            $data['obj'] = objModel::find($id);
            $returnHTML = view($this->folderPath . '.show')->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function edit($id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $data['obj'] = $service->find($id);
            $returnHTML = view($this->folderPath . ".edit")->with($data)->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(ObjRequest $request, $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id, ObjService $service)
    {
        $service->delete($id);
        // $crm = $service->find($id);
        // $crm->is_shown = !$crm->is_shown;
        // $crm->save();
        return jsonSuccess();
    }
}
