<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\BankRequest;
use Nami\Finance\Services\BankService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class BankController extends Controller
{
    public string $folderPath = "finance::finance.banks";
    public string $mainRoute = "finance.banks";

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
                ->addColumn('name', function ($row) {
                    $locale = app()->getLocale();
                    return @$row->translate($locale)->name;
                })
                ->addColumn('code', function ($row) {

                    return $row->accounts->code ?? '--' ;
                })
                ->filterColumn('name', function ($query, $keyword) {
                    $locale = app()->getLocale();
                    $query->whereHas('translations', function ($query) use ($locale, $keyword) {
                        $query->where('locale', $locale)
                        ->where('name', 'LIKE', "%{$keyword}%");
                    });
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.banks");
        $data["addButtonText"] = __("finance::finance.banks");
        // $data["modalType"] = "";
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
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    /**
     * Store a newly created resource in storage.
     *

     */
    public function store(BankRequest $request, ObjService $service)
    {
        $dataInsert = $request->validated();
        $dataInsert['created_by_id'] = auth('admin')->user()->id;
        $bank = $service->store($dataInsert);
        $service->storeBankAccount($bank['bank'], $request,$bank['account']->id);
//       dd($bank);
        return jsonSuccess($bank);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return void
     */
    public function show(int $id)
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
     */
    public function update(BankRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
//        $bank = $service->find($id);
//        $service->storeBankAccount($bank, $request,$dataInsert['account_id']);
        $data = $service->update($id, $dataInsert);
        $bank = $service->find($id);
        $service->updateAccountName($bank->account_id, $dataInsert);
        return jsonSuccess($data);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @param ObjService $service
     * @return JsonResponse
     */
    public function destroy(int $id, ObjService $service,BankRequest $request)
    {
        $service->deleteWithFile($id,'image');
        return jsonSuccess();
    }
}
