<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\CategoryRequest as ObjRequest;
use Nami\Finance\Services\CategoryService as ObjService;
use Yajra\DataTables\DataTables;

class CategoryController extends Controller
{
    public string $folderPath = "finance::finance.categories";
    public string $mainRoute = "finance.categories";


    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('parent_id', function ($row) {
                    return $row->parent->title ?? "";
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = "";
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    if ($row->children->isEmpty() && $row->item_main_category->isEmpty() && $row->item_sub_category->isEmpty()) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.categories");
        $data["addButtonText"] = __("finance::finance.categories");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".create")->with([
                'storeRoute' => route($this->mainRoute . ".store"),
                'category_types' => $service->getCategoryTypes(),
                'categories' => $service->getWhere(["parent_id" => null]),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function store(ObjRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $bank = $service->store($dataInsert);
        return jsonSuccess($bank);
    }

    public function show(int $id, ObjService $service)
    {
        $subCategories = $service->getWhere(['parent_id' => $id]);
        return response()->json($subCategories);
    }

    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                "obj" => $service->find($id),
                'updateRoute' => route($this->mainRoute . ".update", $id),
                'category_types' => $service->getCategoryTypes(),
                'categories' => $service->getWhere(["parent_id" => null]),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $obj = $service->find($id);
        $data = $obj->update($dataInsert);
        return jsonSuccess($data);
    }

    public function destroy(int $id, ObjService $service)
    {
        $service->delete($id);
        return jsonSuccess();
    }
}
