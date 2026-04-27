<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\PrincipleHistoryRequest as ObjRequest;
use Nami\Finance\Services\PrincipleService as ObjService;
use Yajra\DataTables\DataTables;

class TransferPrincipleController extends Controller
{
    public string $folderPath = "finance::finance.transfer_principle";
    public string $mainRoute = "finance.transfer-principle";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|JsonResponse|\Illuminate\View\View
     * @throws Exception
     */

    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $dataTable = $service->getPrincipleTransferDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('type', function ($row) {
                    return @$row->type->name;
                })
                ->addColumn('principle_website_id', function ($row) {
                    return @$row->principle_website->title;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);

                    return $editButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.transfer-principle");
        $data["addButtonText"] = __("finance::finance.transfer-principle");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . ".edit")->with([
                'types' => $service->getTypes(),
                'perished_types' => $service->getPerishedTypes(),
                'perished_accounts' => $service->getAccounts(),
                "obj" => $service->find($id),
                "principle_websites" => $service->getPrincipleWebsites(),
                'updateRoute' => route($this->mainRoute . ".update", $id),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service)
    {
        $dataInsert = $request->validated();
        $data = $service->update($id, ['principle_website_id' => $dataInsert['to_website_id']]);
        $data = $service->storePrincipleHistory($dataInsert, $id);
        return jsonSuccess($data);
    }
}
