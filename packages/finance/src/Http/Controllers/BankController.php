<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\BankRequest;
use Nami\Finance\Services\BankService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class BankController extends Controller
{
    public string $folderPath = 'finance::finance.banks';
    public string $mainRoute = 'finance.banks';

    /** @throws Exception */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return DataTables::of($service->getDataTable())
                ->addIndexColumn()
                ->addColumn('accounts_count', function ($row) {
                    return $row->type === \Nami\Finance\Enums\AccountTypesEnum::Bank
                        ? 1
                        : $row->accounts_count;
                })
                ->addColumn('actions', function ($row) {
                    $editButton = editButton(route($this->mainRoute . '.edit', $row->id), $row->name);
                    $deleteButton = deleteButton(route($this->mainRoute . '.destroy', $row->id));

                    return $editButton . ' ' . $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            'createRoute' => route($this->mainRoute . '.create'),
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'bladeTitle' => __('finance::finance.banks'),
            'addButtonText' => __('finance::finance.bank'),
        ]);
    }

    /** @throws Throwable */
    public function create(Request $request): ?JsonResponse
    {
        if (!$request->ajax()) {
            return null;
        }

        $html = view($this->folderPath . '.create', [
            'storeRoute' => route($this->mainRoute . '.store'),
        ])->render();

        return jsonSuccess(['html' => $html]);
    }

    public function store(BankRequest $request, ObjService $service): JsonResponse
    {
        $data = $request->validated();
        $data['created_by_id'] = auth('admin')->id();

        return jsonSuccess($service->store($data));
    }

    public function show(int $id): void
    {
    }

    /** @throws Throwable */
    public function edit(int $id, Request $request, ObjService $service): ?JsonResponse
    {
        if (!$request->ajax()) {
            return null;
        }

        $html = view($this->folderPath . '.edit', [
            'obj' => $service->findBank($id),
            'updateRoute' => route($this->mainRoute . '.update', $id),
        ])->render();

        return jsonSuccess(['html' => $html]);
    }

    public function update(BankRequest $request, int $id, ObjService $service): JsonResponse
    {
        return jsonSuccess($service->updateBank($id, $request->validated()));
    }

    public function destroy(int $id, BankRequest $request, ObjService $service): JsonResponse
    {
        $service->deleteBank($id);

        return jsonSuccess();
    }
}
