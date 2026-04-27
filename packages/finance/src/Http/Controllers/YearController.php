<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\YearRequest as ObjRequest;
use Nami\Finance\Services\YearService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class YearController extends Controller
{
    public string $folderPath = "finance::finance.years";
    public string $mainRoute = "finance.years";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('image', function ($row) {
                    return getImgTag($row->image);
                })
                ->editColumn('parent_id', function ($row) {
                    return $row->parent->title ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $editButton = "";
                    if ($row->status == 0) {
                        $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    }
                    $deleteButton = '';
                    if(!$row->hasChildrens()){
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    $toggleStatusButton = $row->status == 1 ?
                    '<button class="btn btn-success status-toggle" data-id="'.$row->id.'" data-status="0">'.__("finance::finance.Enable").'</button>' :
                    '<button class="btn btn-danger status-toggle" data-id="'.$row->id.'" data-status="1">'.__("finance::finance.Disable").'</button>' ;

                    return $editButton . " " . $deleteButton . " " . $toggleStatusButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.fn-year");
        $data["addButtonText"] = __("finance::finance.fn-year");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'checkYear'=> $service->getActiveYear()
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param ObjRequest $request
     * @param ObjService $service
     * @return JsonResponse
     */
    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $exists = $service->isDateRangeOverlap($dataInsert);
        if ($exists) {
            return jsonValid( ['error'=>[__("finance.The specified time period overlaps with an existing period in the same year.")]],'');
        }
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $obj = $service->store($dataInsert);
        return jsonSuccess($obj);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(Request $request, $id, ObjService $service)
    {
        //
    }
    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @param Request $request
     * @param ObjService $service
     * @return JsonResponse|void
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param ObjRequest $request
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $exists = $service->isDateRangeOverlapForUpdate($dataInsert, $id);
        if ($exists) {
            return jsonValid( ['error'=>[__("finance.The specified time period overlaps with an existing period in the same year.")]],'');
        }
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service)
    {
        $year = $service->find($id);
        if ($year->constraints->count() > 1) {
            return jsonValid( ['error'=>[__("finance.It is not allowed to delete this item because it is linked to other items")]],'');
        }
        $service->delete($id);
        return jsonSuccess();

    }
}
