<?php

namespace Nami\Crm\Http\Controllers;

use App\Http\Controllers\Controller;
use Nami\Crm\Services\QuestionService as ObjService;
use Nami\Crm\Http\Requests\QuestionRequest as ObjRequest;
use Illuminate\Http\Request;
use Nami\Crm\Models\Question as ObjModel;
use Yajra\DataTables\DataTables;


class QuestionController extends Controller
{
    public $folderPath = "crm::crm.questions";
    public $mainRoute = "crm.questions";

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Contracts\Foundation\Application|\Illuminate\Contracts\View\Factory|\Illuminate\Contracts\View\View|\Illuminate\Http\JsonResponse|\Illuminate\Http\Response
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = ObjModel::query()->where('poll_id', $request->poll_id)->latest();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('questions', function ($row) {
                    return addButton(route("crm.questions.create", $row->id), $row->name, 'question', __("crm.questions"));
                })
                ->addColumn('show', function ($row) {

                    return implode(' , ', $row->answers->pluck('value')->toArray());
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create", ['poll_id' => $request->poll_id]);
        $data["dataTableRoute"] = route($this->mainRoute . ".index", ['poll_id' => $request->poll_id]);
        $data["bladeTitle"] = __("crm.questions");
        $data["addButtonText"] = __("crm.questions");
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

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->storeQuestion($dataInsert);
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
        $data = $service->updateQuestion($id, $dataInsert);
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
