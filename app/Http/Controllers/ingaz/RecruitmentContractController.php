<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Enums\ingaz\OrderEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\StoreContractRequest;
use App\Models\Contract;
use App\Models\ContractInfo;
use App\Models\ContractStatusLog;
use App\Models\ingaz\Contact;
use App\Models\ingaz\Maid;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\ingaz\UserAddress;
use App\Models\TravelHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Yajra\DataTables\Facades\DataTables;

class RecruitmentContractController extends Controller
{
    public $folderPath = "admin.ingaz.RecruitmentContract";
    public $mainRoute = "RecruitmentContract";
    public $contractInfoData = [
        'visa_number',
        'visa_hijry_date',
        'visa_milad_date',
        'working_hours_num'
        ,
        'sponsorship_price',
        'salary_after_sponsorship',
        'monthly_cost',
        'contract_duration'
        ,
        'contract_value',
        'contract_date',
        'ingaz_from',
        'ingaz_to',
        'previous_sponsorship',
        'msaned_number',
        'tafwed_number'
    ];


    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $status = $request->get('status', $status);
            if ($status)
                $rows = Contract::RecruitmentContract()->where('status', $status)->latest();
            else
                $rows = Contract::RecruitmentContract()->latest();

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    return getEnumValue('contract_statuses', $row->status);
                })
                ->editColumn('user_id', function ($row) {
                    return ($row->user) ? $row->user->name : '';
                })
                ->addColumn('show_details', function ($row) {
                    return '<a class="btn btn-md btn-secondary" href="' . route('contracts.details', $row->id) . '"> عرض العقد <i class="fa fa-file mr-2"></i></a>';
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "80px", "80px") : '';
                })
                ->addColumn('updateStatus', function ($row) {
                    if ($row->status != ContractStatusEnum::Back->value && $row->status != ContractStatusEnum::Arrival->value && $row->status != ContractStatusEnum::Tazker->value) {
                        $lastContractStatusLog = $row->getLastContractStatusLog();
                        return '<button class="btn btn-success update-status-btn" data-bs-toggle="modal" data-bs-target="#updateStatusModal" data-row-id="' . $row->id . '" data-status="' . $row->status . '" data-date="' . ($lastContractStatusLog ? $lastContractStatusLog->date : \Carbon\Carbon::now()->format('Y-m-d')) . '">' . contractStatusConfirmBtnText($row->status) . '</button>';
                    } else if ($row->status == ContractStatusEnum::Tazker->value) {
                        // check if there is a travel history for this contract
                        $travelHistory = TravelHistory::where('order_id', $row->id)->first();
                        if ($travelHistory) {
                            return '<a class="btn btn-primary" href="' . route('travel.control', ['id' => $row->id, 'userId' => $row->user_id, 'maidId' => $row->maid_id]) . '">' . __("admin.show_travel") . ' <i class="fa fa-eye"></i></a>';
                        } else {
                            return '<a class="btn btn-success" href="' . route('travel.control', ['id' => $row->id, 'userId' => $row->user_id, 'maidId' => $row->maid_id]) . '">' . __("admin.add_travel") . ' <i class="fa fa-plus"></i></a>';
                        }
                    } else {
                        return '<span class="text-danger">--</span>';
                    }
                })
                ->addColumn('back', function ($row) {
                    if ($row->status == ContractStatusEnum::Back->value) {
                        return '<span class="text-danger">مرتجع</span>';
                    } else {
                        return '<button class="btn btn-danger backContract" data-id="' . $row->id . '">ارجاع العقد <i class="fa fa-undo"></i> </button>';
                    }
                })
                ->addColumn('actions', function ($row) {
                    $deleteButton = '';
                    if (@AdminGuardHasPerm(\App\PermKeys::REC_CONTRACT_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }
                    return $deleteButton;
                })
                ->escapeColumns([])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);
    }


    public function details($id)
    {
        $contract = Contract::with('user', 'maid', 'info', 'contract_status_logs')->findOrFail($id);
        $info = ContractInfo::where('contract_id', $contract->id)->first();
        //        return $contract;
        return view('admin.ingaz.contract.details', compact('contract', 'info'));
    }

    public function create()
    {
        $orders = \App\Models\ingaz\Order::where('status', \App\Enums\ingaz\OrderEnum::New->value)
            ->with(['user', 'maid'])
            ->latest()
            ->get();

        $endedOrders = \App\Models\ingaz\Order::where('job_type_id', 1)
            ->whereIn('status', [\App\Enums\ingaz\OrderEnum::Done->value])
            ->latest()
            ->get();

        $countries = \App\Models\ingaz\Nationality::all();
        $all_available_maids = \App\Models\ingaz\Maid::Shown()->latest()->get();

        return view($this->folderPath . '.create', compact('orders', 'endedOrders', 'countries', 'all_available_maids'));
    }


    public function store(StoreContractRequest $request)
    {
        // Initialize contract data array
        $contractData = [
            'status' => ContractStatusEnum::New ->value,
            'maid_id' => $request->maid_id,
        ];

        // Scenario 1: User selects an existing order to create a contract
        if ($request->order_id) {
            $order = Order::find($request->order_id);
            $contractData['order_id'] = $order->id;
            $contractData['user_id'] = $order->user_id;
            $contractData['maid_id'] = $order->maid_id;
        } // Scenario 2: Admin creates a contract for a new client
        else {
            $phoneExists = User::where('phone', $request->client_phone)->first();
            if ($phoneExists) {
                return jsonError(__("admin.phone_exists"));
            }
            // Create new user
            $newUser = User::create([
                'name' => $request->client_name,
                'phone_code' => "+966",
                'phone' => $request->client_phone,
                'nationality_id' => $request->nationality_id,
                'email' => $request->client_email,
                'national_num' => $request->client_national_num,
                'phone2' => $request->phone2,
                'city' => $request->city,
                'rooms_num' => $request->beds_num,
                'family_num' => $request->family_num,
                'children' => $request->children,
                'job' => $request->job,
            ]);

            // Create user address
            UserAddress::create([
                'user_id' => $newUser->id,
                'address' => $request->address,
            ]);

            // Assign user id to contract data
            $contractData['user_id'] = $newUser->id;
        }

        // Create contract
        $contract = Contract::create($contractData);

        // Save contract info
        $contractInfo = $request->only($this->contractInfoData);
        $contractInfo['contract_id'] = $contract->id;
        ContractInfo::create($contractInfo);

        // Save contract status log
        ContractStatusLog::create([
            'contract_id' => $contract->id,
            'status' => ContractStatusEnum::New ->value,
            'date' => $request->contract_date,
        ]);

        // Update order status to completed if this was created from an order
        if ($request->order_id) {
            $order = Order::find($request->order_id);
            if ($order) {
                $order->status = OrderEnum::Completed->value;
                $order->save();
            }
        }

        return jsonSuccess();
    }


    public function show($id, Request $request)
    {
        $row = Contract::find($id);

        switch ($row->status) {
            case ContractStatusEnum::New ->value:
                $row->status = ContractStatusEnum::Msaned->value;
                break;
            case ContractStatusEnum::Msaned->value:
                $row->status = ContractStatusEnum::Tafwed->value;
                break;
            case ContractStatusEnum::Tafwed->value:
                $row->status = ContractStatusEnum::Taffez->value;
                break;
            case ContractStatusEnum::Taffez->value:
                $row->status = ContractStatusEnum::Tazker->value;
                break;
            case ContractStatusEnum::Tazker->value:
                $row->status = ContractStatusEnum::Arrival->value;
                break;
            case ContractStatusEnum::Sari->value:
                $row->status = ContractStatusEnum::Closed->value;
                break;
            default:
                break;
        }

        // Save the updated contract status
        $row->save();

        // Create or update the contract status log
        $row->contract_status_logs()->create([
            'status' => $row->status,
            'date' => $request->date,
        ]);

        return jsonSuccess();
    }

    public function back($id)
    {
        $contract = Contract::find($id);
        $newStatus = contractBackStatus($contract->status);
        $contract->update(['status' => $newStatus->value]);
        $contract->contract_status_logs()->create(['status' => $newStatus->value]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
    }

    public function destroy($id)
    {
        Contract::find($id)->delete();
        return jsonSuccess();
    }

    public function getMaidByNationality($nationalityId)
    {
        $maids = Maid::where('nationality_id', $nationalityId)->get();
        return response()->json($maids->toArray());
    }

    public function exportPdf()
    {
        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة عقود الاستقدام</title>
            <style>
                body {
                    font-family: Arial, sans-serif;
                    direction: rtl;
                    margin: 20px;
                }
                .header {
                    text-align: center;
                    margin-bottom: 30px;
                    padding: 10px;
                    background-color: #f8f9fa;
                    border-radius: 5px;
                }
                .header h1 {
                    color: #333;
                    margin-bottom: 10px;
                }
                .print-button {
                    display: inline-block;
                    padding: 10px 20px;
                    background-color: #4CAF50;
                    color: white;
                    text-decoration: none;
                    border-radius: 5px;
                    margin-bottom: 20px;
                    cursor: pointer;
                    border: none;
                    font-size: 16px;
                }
                .print-button:hover {
                    background-color: #45a049;
                }
                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin-top: 20px;
                    font-size: 12px;
                }
                th, td {
                    border: 1px solid #ddd;
                    padding: 8px;
                    text-align: right;
                }
                th {
                    background-color: #f2f2f2;
                    color: #333;
                    font-weight: bold;
                }
                tr:nth-child(even) {
                    background-color: #f9f9f9;
                }
                @media print {
                    .print-button {
                        display: none;
                    }
                    body {
                        margin: 0;
                        padding: 20px;
                    }
                    table {
                        page-break-inside: auto;
                    }
                    tr {
                        page-break-inside: avoid;
                        page-break-after: auto;
                    }
                }
            </style>
            <script>
                function printDocument() {
                    window.print();
                }
                window.onload = function() {
                    window.print();
                }
            </script>
        </head>
        <body>
            <div class="header">
                <h1>قائمة عقود الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العقد</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>تاريخ العقد</th>
                        <th>تاريخ البداية</th>
                        <th>تاريخ النهاية</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($contracts as $contract) {
            $html .= '<tr>
                <td>' . $contract->id . '</td>
                <td>' . ($contract->user ? $contract->user->name : '') . '</td>
                <td>' . ($contract->user ? getPhoneWithTag($contract->user->phone_code . $contract->user->phone) : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->name : '') . '</td>
                <td>' . ($contract->maid ? $contract->maid->passport_num : '') . '</td>
                <td>' . ($contract->info ? $contract->info->contract_date : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_from : '') . '</td>
                <td>' . ($contract->info ? $contract->info->ingaz_to : '') . '</td>
                <td>' . getEnumValue('contract_statuses', $contract->status) . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="contracts.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        $contracts = Contract::RecruitmentContract()->with('user', 'maid', 'info')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العقد',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'تاريخ العقد',
            'تاريخ البداية',
            'تاريخ النهاية',
            'الحالة'
        ];

        // Set headers in first row
        $sheet->fromArray($headers, NULL, 'A1');

        // Style headers
        $headerStyle = [
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
                    'rgb' => 'F2F2F2',
                ],
            ],
        ];
        $sheet->getStyle('A1:I1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($contracts as $contract) {
            $sheet->setCellValue('A' . $row, $contract->id);
            $sheet->setCellValue('B' . $row, $contract->user ? $contract->user->name : '');
            $sheet->setCellValue('C' . $row, $contract->user ? $contract->user->phone_code . $contract->user->phone : '');
            $sheet->setCellValue('D' . $row, $contract->maid ? $contract->maid->name : '');
            $sheet->setCellValue('E' . $row, $contract->maid ? $contract->maid->passport_num : '');
            $sheet->setCellValue('F' . $row, $contract->info ? $contract->info->contract_date : '');
            $sheet->setCellValue('G' . $row, $contract->info ? $contract->info->ingaz_from : '');
            $sheet->setCellValue('H' . $row, $contract->info ? $contract->info->ingaz_to : '');
            $sheet->setCellValue('I' . $row, getEnumValue('contract_statuses', $contract->status));
            $row++;
        }

        // Auto-size columns
        foreach(range('A','I') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="contracts.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'contracts.xlsx', $headers)->deleteFileAfterSend(true);
    }
}
