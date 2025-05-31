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
use App\Models\Country;
use App\Actions\OrderAction;
use App\Actions\MaidAction;
use App\Actions\ClientAction;
use App\Actions\NationalityAction;
use App\Actions\ReligionAction;
use App\Actions\JobTitleAction;
use App\Actions\JobTypeAction;
use Illuminate\Support\Facades\DB;

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
            if ($status === 'under_warranty') {
                $rows = Contract::RecruitmentContract()->underWarranty()->latest();
            } else if ($status) {
                $rows = Contract::RecruitmentContract()->where('status', $status)->latest();
            } else {
                $rows = Contract::RecruitmentContract()->latest();
            }

            return DataTables::of($rows)
                ->addIndexColumn()
                ->editColumn('status', function ($row) {
                    if ($row->status === ContractStatusEnum::Arrival->value) {
                        $warrantyEndDate = $row->updated_at->addDays(90);
                        $remainingDays = now()->diffInDays($warrantyEndDate, false);

                        if ($remainingDays > 0) {
                            return getEnumValue('contract_statuses', $row->status) .
                                   ' <span class="badge bg-info">' .
                                   __('admin.remaining_warranty_days', ['days' => $remainingDays]) .
                                   '</span>';
                        } else {
                            return getEnumValue('contract_statuses', $row->status) .
                                   ' <span class="badge bg-danger">' .
                                   __('admin.warranty_expired') .
                                   '</span>';
                        }
                    }
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
                            $tripStatus = $travelHistory->status === 'completed' ?
                                '<span class="badge bg-success">' . __('admin.trip_completed') . '</span>' :
                                '<span class="badge bg-warning">' . __('admin.trip_pending') . '</span>';

                            $buttons = $tripStatus;

                            // Add complete trip button if trip is not completed
                            if ($travelHistory->status !== 'completed') {
                                $buttons .= ' <button class="btn btn-success complete-trip-btn" data-trip-id="' . $travelHistory->id . '">' . __('admin.mark_trip_completed') . '</button>';
                            }

                            // Add show travel button
                            $buttons .= ' <a class="btn btn-primary" href="' . route('travel.control', ['id' => $row->id, 'userId' => $row->user_id, 'maidId' => $row->maid_id]) . '">' . __("admin.show_travel") . ' <i class="fa fa-eye"></i></a>';

                            return $buttons;
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
        $orderId = request('orderId');
        \Log::info('Loading create page with orderId: ' . $orderId);

        // Load the selected order with all necessary relationships
        $selectedOrder = null;
        if ($orderId) {
            $selectedOrder = Order::with([
                'user.nationality',
                'maid.nationality',
                'maid.religion',
                'maid.job_types',
                'jobType.translations'
            ])->find($orderId);
            \Log::info('Selected order loaded:', ['order' => $selectedOrder ? $selectedOrder->toArray() : null]);
        }

        // Get new orders with relationships
        $orders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.job_types',
            'jobType.translations'
        ])
        ->where('status', 'new')
        ->get();

        // Get ended orders with relationships
        $endedOrders = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.job_types',
            'jobType.translations'
        ])
        ->where('status', 'ended')
        ->get();

        // Get all available maids with relationships
        $all_available_maids = Maid::with([
            'nationality',
            'religion',
            'job_types'
        ])->get();

        // If we have a selected order and it's not in the new orders list, add it
        if ($selectedOrder && !$orders->contains('id', $selectedOrder->id)) {
            $orders->prepend($selectedOrder);
        }

        $countries = Country::all();
        return view('admin.ingaz.RecruitmentContract.create', compact('orders', 'endedOrders', 'countries', 'all_available_maids', 'selectedOrder', 'orderId'));
    }


    public function store(StoreContractRequest $request)
    {
        \Log::info('Recruitment Contract Creation Request:', $request->all());

        try {
            DB::beginTransaction();

            $contractData = [
                'status' => ContractStatusEnum::New->value,
                'maid_id' => $request->maid_id,
            ];

            if ($request->order_id) {
                $order = Order::findOrFail($request->order_id);
                \Log::info('Found order:', ['order' => $order->toArray()]);

                $contractData['user_id'] = $order->user_id;
                $contractData['maid_id'] = $order->maid_id;

                \Log::info('Contract data from order:', $contractData);
            } else {
                $user = User::where('phone', $request->client_phone)->first();
                if (!$user) {
                    $user = User::create([
                        'name' => $request->client_name,
                        'phone' => $request->client_phone,
                        'nationality_id' => $request->client_nationality_id,
                        'national_num' => $request->client_national_num,
                        'email' => $request->client_email,
                        'phone2' => $request->phone2,
                        'city' => $request->city,
                        'beds_num' => $request->beds_num,
                        'family_num' => $request->family_num,
                        'children' => $request->children,
                        'job' => $request->job,
                        'address' => $request->address,
                    ]);
                    \Log::info('Created new user:', ['user' => $user->toArray()]);
                }
                $contractData['user_id'] = $user->id;
            }

            \Log::info('Creating contract with data:', $contractData);

            $contract = Contract::create($contractData);
            \Log::info('Contract created:', ['contract' => $contract->toArray()]);

            // حفظ معلومات العقد
            $contractInfo = [
                'contract_date' => $request->contract_date,
                'msaned_number' => $request->msaned_number,
                'tafwed_number' => $request->tafwed_number,
                'visa_number' => $request->visa_number,
                'visa_milad_date' => $request->visa_milad_date,
                'visa_hijry_date' => $request->visa_hijry_date,
                'contract_duration' => $request->contract_duration,
            ];

            \Log::info('Creating contract info:', $contractInfo);

            $contract->info()->create($contractInfo);
            \Log::info('Contract info created');

            // حفظ سجل حالة العقد
            $contract->contract_status_logs()->create(['status' => ContractStatusEnum::New->value]);
            \Log::info('Contract status log created');

            // تحديث حالة الطلب إذا كان العقد من طلب
            if ($request->order_id) {
                $order->update(['status' => OrderEnum::Completed->value]);
                \Log::info('Order status updated to completed');
            }

            DB::commit();

            \Log::info('Contract created successfully', ['contract_id' => $contract->id]);

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء العقد بنجاح',
                'contract' => $contract->load(['user', 'maid', 'info'])
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error creating contract:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'request_data' => $request->all()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
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

    public function completeTrip($tripId)
    {
        try {
            \Log::info('Starting trip completion process for trip ID: ' . $tripId);

            $travelHistory = TravelHistory::findOrFail($tripId);
            \Log::info('Found travel history:', ['travel' => $travelHistory->toArray()]);

            $contract = Contract::where('id', $travelHistory->order_id)->first();
            \Log::info('Found contract:', ['contract' => $contract ? $contract->toArray() : null]);

            if (!$contract) {
                \Log::error('Contract not found for travel history ID: ' . $tripId);
                return response()->json([
                    'success' => false,
                    'message' => __('admin.contract_not_found')
                ]);
            }

            // Update travel history status
            $travelHistory->status = 'completed';
            $travelHistory->save();
            \Log::info('Updated travel history status to completed');

            // Update contract status to Arrival
            $contract->status = ContractStatusEnum::Arrival->value;
            $contract->save();
            \Log::info('Updated contract status to Arrival');

            // Create contract status log
            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Arrival->value,
                'notes' => 'تم اكتمال الرحلة والانتقال إلى مرحلة تحت الضمان',
                'date' => now()
            ]);
            \Log::info('Created contract status log');

            // Force refresh the contract status
            $contract->refresh();

            return response()->json([
                'success' => true,
                'message' => __('admin.trip_completed_success'),
                'contract_status' => $contract->status
            ]);
        } catch (\Exception $e) {
            \Log::error('Error in completeTrip: ' . $e->getMessage(), [
                'tripId' => $tripId,
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => __('admin.something_went_wrong')
            ]);
        }
    }
}
