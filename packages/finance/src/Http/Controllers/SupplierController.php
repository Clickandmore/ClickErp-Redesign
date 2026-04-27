<?php

namespace Nami\Finance\Http\Controllers;

use App\Enums\Office\OfficesStatusEnum;
use App\Http\Controllers\admin\Controller;
use App\Models\office\Office;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\SupplierRequest as ObjRequest;
use Nami\Finance\Services\SupplierService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class SupplierController extends Controller
{
    public string $folderPath = "finance::finance.supplier";
    public string $mainRoute = "finance.suppliers";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        if (!$request->ajax() && $request->get('source') === 'offices') {
            return redirect()->route('finance.external-agents.index');
        }

        if ($request->ajax()) {
            if ($request->get('source') === 'offices') {
                return $this->externalAgentsDataTable();
            }

            $dataTable = $service->getDataTable();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->addColumn('supplier_type_id', function ($data) {
                    return $data->supplierType->name ?? '--';
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    return $editButton . " " . $deleteButton;
                })->escapeColumns([])->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("finance::finance.suppliers");
        $data["addButtonText"] = __("finance::finance.Supplier");
        return view($this->folderPath . '.index', $data);
    }

    public function externalAgentsIndex(Request $request)
    {
        if ($request->ajax()) {
            return $this->externalAgentsDataTable();
        }

        $data["dataTableRoute"] = route('finance.external-agents.index');
        $data["createRoute"] = route('offices.create');
        $data["bladeTitle"] = __("finance::finance.external_agents");

        return view('finance::finance.external_agents.index', $data);
    }

    protected function externalAgentsDataTable()
    {
        $dataTable = Office::query()
            ->with(['nationality', 'jobTypes', 'account'])
            ->latest();

        return DataTables::of($dataTable)
            ->addIndexColumn()
            ->addColumn('title', function ($row) {
                return $row->title ?? '--';
            })
            ->addColumn('account_code', function ($row) {
                return $row->account?->code ? (string) $row->account->code : '--';
            })
            ->addColumn('nationality', function ($row) {
                return $row->nationality->title ?? '--';
            })
            ->addColumn('office_type', function ($row) {
                $jobTypes = $row->jobTypes->pluck('title')->filter()->implode('، ');

                return $jobTypes !== '' ? $jobTypes : '--';
            })
            ->addColumn('status_label', function ($row) {
                return OfficesStatusEnum::tryFrom((string) $row->status)?->lang() ?? '--';
            })
            ->addColumn('actions', function ($row) {
                $showButton = '<a href="' . route('offices.show', $row->id) . '" class="btn btn-sm btn-info me-1" title="عرض">
                        <i class="fa fa-eye"></i>
                    </a>';
                $editButton = '<a href="' . route('offices.edit', $row->id) . '" class="btn btn-sm btn-primary" title="تعديل">
                        <i class="fa fa-pencil"></i>
                    </a>';

                return $showButton . $editButton;
            })
            ->escapeColumns([])
            ->make(true);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return JsonResponse
     * @throws Throwable
     */
    public function create(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            try {
                $parent = $service->getParentOrFail();
                $returnHTML = view($this->folderPath . ".create")->with([
                    'code' => $service->getChildCode($parent),
                    "storeRoute" => route($this->mainRoute . ".store"),
                ])->render();

                return jsonSuccess(["html" => $returnHTML]);
            } catch (Throwable $throwable) {
                return jsonError($throwable->getMessage(), 422);
            }
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
        try {
            $dataInsert = $request->validated();
            $dataInsert['created_by_id'] = auth('admin')->user()->id;
            $data = $service->storeSupplier($dataInsert);

            return jsonSuccess($data);
        } catch (Throwable $throwable) {
            return jsonError($throwable->getMessage(), 422);
        }
    }


    public function show($id)
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
                "updateRoute" => route($this->mainRoute . ".update", $id),
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
        $dataUpdated = $request->validated();
        $data = $service->updateSupplier($id, $dataUpdated);
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
        $serviceObj = $service->find($id);
        deleteFile($serviceObj->tax_image);
        deleteFile($serviceObj->commercial_register_image);
        deleteFile($serviceObj->national_address);
        $service->deleteFiles($id);
        $service->delete($id);
        return jsonSuccess();
    }
}
