<?php

namespace Nami\Hr\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Hr\Http\Requests\EmployeeCustody\EmployeeCustodyRequest as ObjRequest;
use Nami\Hr\Models\Employee;
use Nami\Hr\Models\EmployeeCustody as objModel;
use Nami\Hr\Models\EmployeeCustodyLocation;
use Nami\Hr\Services\EmployeeCustodyService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class EmployeeCustodyController extends Controller
{
    public string $folderPath = 'hr::hr.employeeCustodies';
    public string $mainRoute = 'employee-custodies';

    /**
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            return DataTables::of($service->getDataTable($request->user_id))
                ->addIndexColumn()
                ->editColumn('employee_id', fn ($row) => $row->employee->name ?? '')
                ->editColumn('account_id', fn ($row) => $row->account ? ($row->account->code . ' - ' . $row->account->name) : '-')
                ->editColumn('location', fn ($row) => $row->custodyLocation->name ?? $row->location ?? '-')
                ->editColumn('value', fn ($row) => number_format((float) $row->value, 2))
                ->editColumn('received_at', fn ($row) => optional($row->received_at)->format('Y-m-d') ?: '-')
                ->editColumn('returned_at', fn ($row) => optional($row->returned_at)->format('Y-m-d') ?: '-')
                ->editColumn('status', fn ($row) => $row->status_label)
                ->editColumn('file', function ($row) {
                    if ($row->file) {
                        return "<a target='_blank' href='" . showFile($row->file) . "'>" . __('hr::hr.view_attachment') . "</a>"
                            . " <a download href='" . showFile($row->file) . "'><i class='fa fa-download'></i></a>";
                    }

                    return __('hr::hr.no_file');
                })
                ->addColumn('actions', function ($row) {
                    return editButton(route($this->mainRoute . '.edit', $row->id), __('hr::hr.employee_custody'))
                        . ' '
                        . deleteButton(route($this->mainRoute . '.destroy', $row->id));
                })
                ->escapeColumns([])
                ->make(true);
        }

        return view($this->folderPath . '.index', [
            'createRoute' => route($this->mainRoute . '.create'),
            'dataTableRoute' => route($this->mainRoute . '.index'),
            'bladeTitle' => __('hr::hr.employee_custodies'),
            'addButtonText' => __('hr::hr.employee_custody'),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function create(Request $request)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.create')->with([
                'storeRoute' => route($this->mainRoute . '.store'),
                'employees' => Employee::query()->get(),
                'locations' => EmployeeCustodyLocation::query()->where('is_active', true)->orderBy('name')->get(),
                'statuses' => objModel::statuses(),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function store(ObjRequest $request, ObjService $service): JsonResponse
    {
        $service->storeCustody($request->validated());

        return jsonSuccess();
    }

    /**
     * @throws Throwable
     */
    public function edit(int $id, Request $request, ObjService $service)
    {
        if ($request->ajax()) {
            $returnHTML = view($this->folderPath . '.edit')->with([
                'obj' => $service->find($id),
                'updateRoute' => route($this->mainRoute . '.update', $id),
                'employees' => Employee::query()->get(),
                'locations' => EmployeeCustodyLocation::query()->where('is_active', true)->orderBy('name')->get(),
                'statuses' => objModel::statuses(),
            ])->render();

            return jsonSuccess(['html' => $returnHTML]);
        }
    }

    public function update(ObjRequest $request, int $id, ObjService $service): JsonResponse
    {
        $service->updateCustody($id, $request->validated());

        return jsonSuccess();
    }

    public function destroy(int $id, ObjService $service): JsonResponse
    {
        $service->delete($id);

        return jsonSuccess();
    }
}
