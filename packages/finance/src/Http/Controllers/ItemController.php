<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\ItemRequest as ObjRequest;
use Nami\Finance\Services\ItemService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class ItemController extends Controller
{
    public string $folderPath = "finance::finance.items";
    public string $mainRoute = "finance.items";

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
                ->editColumn('main_category_id', function ($row) {
                    return $row->mainCategory->title ?? "";
                })
                ->editColumn('type', function ($row) {
                    return $row->type->lang();
                })
                ->addColumn('actions', function ($row) {
                //     $editButton =  '<a type="button" class="btn btn-outline-secondary"
                //     href="' . route($this->mainRoute . ".edit", $row->id) . '">
                // <i class="fa fa-pencil"></i>
                // </a>';
                    $showButton =  '<a type="button" class="btn btn-outline-secondary"
                                    href="' . route($this->mainRoute . ".show", $row->id) . '">
                                <i class="fa fa-eye"></i>
                                </a>';
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $showButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["excelRoute"] = route("finance.items-import.create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.items");
        $data["addButtonText"] = __("finance::finance.items");
         $data["modalType"] = "";
//        dd($data["accountTree"]);
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
        $data["storeRoute"] = route($this->mainRoute . ".store");
        $data["bladeTitle"] = __("finance::finance.items");
        $data = array_merge($data, $service->getCreateData());
        return view($this->folderPath . '.create', $data);
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
        $obj = $service->storeItem($dataInsert);
        return jsonSuccess($obj, null, 201);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id, Request $request, ObjService $service)
    {
        $data["updateRoute"] = route($this->mainRoute . ".update", $id);
        $data["bladeTitle"] = __("finance::finance.items");
        $data = array_merge($data, $service->getEditData($id));
        return view($this->folderPath . '.show', $data);
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
        $data["updateRoute"] = route($this->mainRoute . ".update", $id);
        $data["bladeTitle"] = __("finance::finance.items");
        $data = array_merge($data, $service->getEditData($id));
        return view($this->folderPath . '.edit', $data);
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
        $data = $service->update($id, $dataInsert);
        return jsonSuccess($data,null,201);
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
        $service->deleteWithFile($id,'image');
        return jsonSuccess();
    }
}
