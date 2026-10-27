<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\BoxRequest as ObjRequest;
use Nami\Finance\Services\BoxService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class BoxController extends Controller
{
    public string $folderPath = "finance::finance.boxes";
    public string $mainRoute = "finance.boxes";

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
                ->addColumn('code', function ($row) {
                    // صندوق حُذف حسابه من الشجرة: يُعرض بدل كسر الجدول، والحفظ من التعديل يعيد إنشاء الحساب
                    return $row->account?->code
                        ?? '<span class="badge bg-danger" title="احفظ الصندوق من زر التعديل لإعادة إنشاء حسابه">الحساب محذوف</span>';
                })

                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));

                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.boxs");
        $data["addButtonText"] = __("finance::finance.boxs");
         $data["modalType"] = "";
//        dd($data["accountTree"]);
        return view($this->folderPath . '.index', $data);
    }

    private function branchAssignmentData(): array
    {
        $enabled = \App\Support\SystemModuleManager::isEnabled('branches')
            && \App\Support\BranchSharingSettings::accountsPerBranch();
        return [
            'accountBranchAssignmentsEnabled' => $enabled,
            'branches' => $enabled ? \App\Models\Branch::active()->ordered()->get() : collect(),
        ];
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
                ...$this->branchAssignmentData(),
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
                "obj" => $service->find($id)->load('account.branches'),
                ...$this->branchAssignmentData(),
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
        $bank = $service->find($id);
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
    public function destroy(int $id, ObjService $service,ObjRequest $request)
    {
        $service->deleteWithFile($id,'image');
        return jsonSuccess();
    }
}
