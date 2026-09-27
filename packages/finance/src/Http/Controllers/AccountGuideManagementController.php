<?php

namespace Nami\Finance\Http\Controllers;

use App\Http\Controllers\admin\Controller;
use Exception;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Nami\Finance\Http\Requests\AccountRequest as ObjRequest;
use Nami\Finance\Services\AccountService as ObjService;
use Throwable;
use Yajra\DataTables\DataTables;

class AccountGuideManagementController extends Controller
{
    public string $folderPath = "finance::finance.accounting_management";
    public string $mainRoute = "finance.accounting-guide-management";
    public string $accountMainRoute = "finance.accounts";

    /**
     * Display a listing of the resource.
     *
     * @return Application|Factory|View|\Illuminate\Foundation\Application|\Illuminate\View\View
     * @throws Exception
     */
    public function index(Request $request, ObjService $service)
    {

        if ($request->ajax()) {
            $dataTable = $service->getDatatableData();
            return DataTables::of($dataTable)
                ->addIndexColumn()
                ->editColumn('type', function ($row) {
                    return $row->type?->lang() ?? "";
                })
                ->editColumn('status', function ($row) {
                    return $row->status?->lang() ?? "";
                })
                ->addColumn('main_account', function ($row) {
                    return $row->parent->name ?? __("finance::finance.without");
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = "";
                    $hasConstraints = (int) ($row->constraint_details_count ?? 0) > 0;
                    $addButton = '<button type="button" class="btn btn-success addButton ms-1" data-bs-toggle="modal" data-bs-target="#createModal"
                        model-route="' . route($this->accountMainRoute . '.create', ['parent_id' => $row->id]) . '" model-title="' . $row->name . '">
                        <i class="fa fa-plus-circle"></i>
                        </button>';
                    $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                    if ($row->operation_type == 3 && !$hasConstraints) {
                        $deleteButton = '<button type="button" class="btn btn-sm btn-danger btn-delete deleteButton" delete-route="' . route($this->accountMainRoute . ".destroy", $row->id) . '" title="حذف">
                              <i class="fa fa-trash-o"></i>
                        </button>';
                    }

                    return $addButton . " " . $editButton . " " . $deleteButton;
                })->rawColumns(['actions'])->make(true);
        }
        $data["createRoute"] = route($this->accountMainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["exportRouteTemplate"] = route($this->mainRoute . ".export", ['format' => '__FORMAT__']);
        $data["bladeTitle"] = __("finance::finance.Accounting guide management");
        $data["addButtonText"] = __("finance::finance.account");
        $data['main_accounts'] = $service->getMainAccounts();
        $data['account_types'] = $service->getAccountTypesList();
        $data['account_statuses'] = $service->getAccountTypes();
        $data['levels'] = $service->getLevels();
        return view($this->folderPath . '.index', $data);
    }

    public function export(Request $request, ObjService $service, string $format = 'xlsx')
    {
        $format = strtolower($format);
        if (!in_array($format, ['csv', 'xlsx', 'excel'], true)) {
            abort(404);
        }

        $format = $format === 'excel' ? 'xlsx' : $format;
        $accounts = $service->getDatatableData()->get();
        $headers = $this->exportHeadings();
        $rows = $this->exportRows($accounts);

        return $format === 'csv'
            ? $this->exportCsvFile($rows, $headers)
            : $this->exportExcelFile($rows, $headers);
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
            $returnHTML = view($this->folderPath . ".create")->with([
                "accountTypes" => $service->getAccountTypes(),
                "accounts" => $service->getValidParents(),
                "types" => $service->getAccountTypesList(),
                "operationTypes" => $service->getOperationTypesList(),
                "storeRoute" => route($this->mainRoute . ".store"),
            ])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
        return $service->getAccountTree();
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
        $data = $service->storeAccount($dataInsert);
        return jsonSuccess($data, null, 201);
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
                "accountTypes" => $service->getAccountTypes(),
                "accounts" => $service->getValidParents(),
                "obj" => $service->find($id),
                "types" => $service->getAccountTypesList(),
                "operationTypes" => $service->getOperationTypesList(),
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
        $account = $service->find($id);
        $account->update(['name' => $dataUpdated['name']]);
        return jsonSuccess(true, null, 201);
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
        $service->delete($id);
        return jsonSuccess(null, null, 201);
    }

    private function exportHeadings(): array
    {
        return [
            __('finance::finance.name'),
            __('finance::finance.code'),
            __('finance::finance.type'),
            __('finance::finance.status'),
            __('finance::finance.level'),
            __('finance::finance.main account'),
        ];
    }

    private function exportRows($accounts): array
    {
        return $accounts->map(function ($account) {
            return [
                $account->name,
                $account->code,
                $account->type?->lang() ?? '',
                $account->status?->lang() ?? '',
                $account->level,
                $account->parent->name ?? __('finance::finance.without'),
            ];
        })->values()->all();
    }

    private function exportCsvFile(array $rows, array $headers)
    {
        return response()->streamDownload(function () use ($rows, $headers) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($file, $headers);

            foreach ($rows as $row) {
                fputcsv($file, $row);
            }

            fclose($file);
        }, $this->buildExportFilename('csv'), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function exportExcelFile(array $rows, array $headers)
    {
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setRightToLeft(app()->getLocale() === 'ar');
        $sheet->fromArray($headers, null, 'A1');

        $headerRange = 'A1:' . \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count($headers)) . '1';
        $sheet->getStyle($headerRange)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER,
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => [
                    'rgb' => 'F2F4F7',
                ],
            ],
        ]);

        $rowNumber = 2;
        foreach ($rows as $row) {
            $sheet->fromArray($row, null, 'A' . $rowNumber);
            $rowNumber++;
        }

        foreach (range(1, count($headers)) as $columnIndex) {
            $columnLetter = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($columnIndex);
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
        $tempFile = tempnam(sys_get_temp_dir(), 'accounting_guide_');
        $writer->save($tempFile);

        return response()
            ->download(
                $tempFile,
                $this->buildExportFilename('xlsx'),
                ['Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet']
            )
            ->deleteFileAfterSend(true);
    }

    private function buildExportFilename(string $format): string
    {
        return 'accounting-guide-management-' . now()->format('Ymd_His') . '.' . $format;
    }
}
