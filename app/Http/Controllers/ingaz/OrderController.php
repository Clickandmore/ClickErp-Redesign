<?php

namespace App\Http\Controllers\ingaz;

use App\Models\ingaz\Maid;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use Illuminate\Http\Request;
use App\Enums\ingaz\OrderEnum;
use App\Models\ingaz\JobTitle;
use App\Models\ingaz\Religion;
use App\Enums\auth\AdminTypeis;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\UserAddress;
use App\Models\ingaz\MaidJobTitles;
use App\Http\Controllers\Controller;
use App\Models\ingaz\OrderTransfers;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\File;
use App\Models\ingaz\Order as objModel;
use Yajra\DataTables\Facades\DataTables;
use App\Http\Actions\ingaz\OrderAction as objAction;

class OrderController extends Controller
{

    public $folderPath = "admin.ingaz.orders";
    public $postData = ["status"];
    public $mainRoute = "orders";

    public function __construct()
    {
        // $this->middleware('permission:الطلبات-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الطلبات-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الطلبات-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الطلبات-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request, $status = null)
    {
        if ($request->ajax()) {
            $query = objModel::query();

            // Order by created_at desc to show newest orders first
            $query->orderBy('created_at', 'desc');

            // Apply status filter
            if ($status) {
                $query->whereIn('status', $this->getSelectedStatus($status));
            }

            // Apply date range filter
            if ($request->filled('from_date')) {
                $query->whereDate('created_at', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('created_at', '<=', $request->to_date);
            }

            // Apply status filter from form
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Apply job type filter
            if ($request->filled('job_type')) {
                $query->where('job_type_id', $request->job_type);
            }

            // Apply admin filter
            if ($request->filled('admin_id')) {
                $query->whereHas('details', function($q) use ($request) {
                    $q->where('admin_id', $request->admin_id);
                });
            }

            // Apply client name search
            if ($request->filled('client_name')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->client_name . '%');
                });
            }

            // Apply phone search
            if ($request->filled('phone')) {
                $query->whereHas('user', function($q) use ($request) {
                    $q->where('phone', 'like', '%' . $request->phone . '%')
                      ->orWhere('phone_code', 'like', '%' . $request->phone . '%');
                });
            }

            // Apply passport number search
            if ($request->filled('passport_num')) {
                $query->whereHas('maid', function($q) use ($request) {
                    $q->where('passport_num', 'like', '%' . $request->passport_num . '%');
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('admin_name', function ($row) {
                    return $row->details->admin->name ?? '-';
                })
                ->addColumn('created_at', function ($row) {
                    return $row->created_at->format('Y-m-d H:i:s');
                })
                ->editColumn('status', function ($row) {
                    return $this->getStatusHtml($row->status);
                })
                ->editColumn('job_type_id', function ($row) {
                    return ($row->jobType) ? $row->jobType->title : '';
                })
                ->editColumn('user_id', function ($row) {
                    return ($row->user) ? $row->user->name : '';
                })
                ->editColumn('phone', function ($row) {
                    return ($row->user) ? getPhoneWithTag($row->user->phone_code . $row->user->phone) : '';
                })
                ->editColumn('maid_id', function ($row) {
                    return ($row->maid) ? getImgTag($row->maid->cv_file, "80px", "80px") : '';
                })
                ->editColumn('nationality_name', function ($row) {
                    return ($row->maid) ? $row->maid->nationality->title : '';
                })
                ->editColumn('maid_passport_num', function ($row) {
                    return ($row->maid) ? $row->maid->passport_num : '';
                })
                ->editColumn('deliver_status', function ($row) {
                    if (@$row->details->deliver_maid == '1') {
                        return '<span class="badge bg-success">' . __('web.yes') . '</span>';
                    } else {
                        return '<span class="badge bg-danger">' . __('web.no') . '</span>';
                    }
                })
                ->addColumn('contract_status', function ($row) {
                    if ($row->contracts()->exists()) {
                        return '<span class="badge bg-success">' . __('ingaz.has_contract') . '</span>';
                    }
                    return '<span class="badge bg-warning">' . __('ingaz.no_contract') . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $showButton = '';
                    $editButton = '';
                    $deleteButton = '';
                    $authorizeButton = '';

                    // Show view button for all orders
                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_VIEW)) {
                        $showButton = '<a href="' . route('showSingleOrder', $row->id) . '" class="btn btn-sm btn-info">
                            <i class="fa fa-eye"></i> ' . __('ingaz.view') . '
                        </a>';
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_EDIT)) {
                        // Only show edit button if order is not completed
                        if ($row->status != OrderEnum::Completed->value) {
                            $editButton = editButton(route($this->mainRoute . ".edit", $row->id), $row->name);
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_ADD_CONTRACT)) {
                        // Add authorization button for orders under process or contracted without contract
                        if ($row->status == OrderEnum::UnderProcess->value ||
                            ($row->status == OrderEnum::Contracted->value && !$row->contracts()->exists())) {
                            $authorizeButton = '<button class="btn btn-sm btn-primary addNewContract" data-id="' . $row->id . '">
                                <i class="fa fa-check"></i> ' . __('ingaz.authorize') . '
                            </button>';
                        }
                    }

                    if (@AdminGuardHasPerm(\App\PermKeys::ORDERS_DELETE)) {
                        $deleteButton = deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $showButton . " " . $editButton . " " . $authorizeButton . " " . $deleteButton;
                })
                ->escapeColumns([])
                ->rawColumns(['actions'])
                ->make(true);
        }

        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["modalType"] = "modal-lg";
        return view($this->folderPath . '.index', $data);
    }

    private function getSelectedStatus($status)
    {
        if ($status == 'current') {
            return [OrderEnum::New->value, OrderEnum::UnderProcess->value];
        } elseif ($status == 'ended') {
            return [OrderEnum::Completed->value, OrderEnum::Contracted->value];
        } elseif ($status == 'not_completed') {
            return [OrderEnum::Refused->value, OrderEnum::Return->value];
        } else {
            return OrderEnum::cases();
        }
    }

    public function edit(Request $request, $id)
    {
        if ($request->ajax()) {
            $obj = objModel::findorfail($id);
            $returnHTML = view($this->folderPath . '.edit')
                ->with(['obj' => $obj])->render();
            return jsonSuccess(["html" => $returnHTML]);
        }
    }

    public function update(request $request, objAction $action, $id)
    {
        $unitData = $request->only($this->postData);
        $data = $action->updateOrderStatus($id, $unitData);
        return jsonSuccess($data);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        $action->delete($id);
        return jsonSuccess();
    }

    public function getStatusHtml($status)
    {
        $colorMap = [
            'new' => 'warning',
            'paid' => 'primary',
            'completed' => 'success',
            'started' => 'info',
            'done' => 'success',
            'refused' => 'danger',
            'msaned' => 'primary',
            'training' => 'primary',
            'arrived' => 'info',
            'escape' => 'danger',
            'deportation' => 'info',
            'delivered' => 'success',
            'under_process' => 'info',
            'contracted' => 'success',
            'contracted_without_contract' => 'warning',
        ];

        $arabicTranslation = getEnumValue('orders_statuses', $status);

        if ($arabicTranslation !== "undefined" && isset($colorMap[$status])) {
            $colorClass = $colorMap[$status];
            return '<span class="badge bg-' . $colorClass . '">' . $arabicTranslation . '</span>';
        }

        return '<span class="text-muted">غير معروف</span>';
    }

    public function showDataOfOrder(request $request)
    {
        $order = Order::find($request->orderId);

        // adjust user data
        $data['user'] = User::find($order->user_id) ?? null;
        $address = UserAddress::where('user_id', $order->user_id)->latest()->first();
        $data['user']['address'] = $address ? $address->address . ' - ' . $address->title : null;

        // adjust maid data
        $data['maid'] = Maid::find($order->maid_id);
        $data['maid']['nationality'] = Nationality::find($data['maid']->nationality_id)->title;
        $data['maid']['religion'] = Religion::find($data['maid']->religion_id)->title;
        $jobsIDS = MaidJobTitles::where('maid_id', $data['maid']->id)->pluck('job_title_id')->toArray();
        $JobTitles = JobTitle::whereIn('id', $jobsIDS)->latest()->get();
        foreach ($JobTitles as $job) {
            $data['maid']['job'] = $data['maid']['job'] . " " . $job->title;
        }

        return jsonSuccess(["data" => $data]);
    }

    public function showDataOfMaid(request $request)
    {
        // adjust maid data
        $data['maid'] = Maid::find($request->maidId);
        $data['maid']['nationality'] = Nationality::find($data['maid']->nationality_id)->title;
        $data['maid']['religion'] = Religion::find($data['maid']->religion_id)->title;
        $jobsIDS = MaidJobTitles::where('maid_id', $data['maid']->id)->pluck('job_title_id')->toArray();
        $JobTitles = JobTitle::whereIn('id', $jobsIDS)->latest()->get();
        foreach ($JobTitles as $job) {
            $data['maid']['job'] = $data['maid']['job'] . " " . $job->title;
        }

        return jsonSuccess(["data" => $data]);
    }

    public function showUserFromContract(request $request)
    {
        // adjust user data
        $data['user'] = User::find($request->contractId);
        $address = UserAddress::where('user_id', $data['user']->id)->latest()->first();
        $data['user']['address'] = $address ? $address->address . ' - ' . $address->title : null;
        return jsonSuccess(["data" => $data]);
    }

    public function showSingleOrder($orderId)
    {
        $data = Order::with([
            'user.nationality',
            'maid.nationality',
            'maid.religion',
            'maid.jobTitles',
            'jobType',
            'details.admin',
            'details.package',
            'details.address',
            'transfers',
            'contracts'
        ])->find($orderId);

        if (!$data) {
            abort(404);
        }

        return view('admin.ingaz.orders.show', compact('data'));
    }

    public function download($id)
    {
        $transfer = OrderTransfers::find($id); // Corrected the namespace

        if (!$transfer) {
            abort(404);
        }

        $filePath = storage_path('app/public/' . $transfer->file); // Adjusted the path for the 'public' disk

        if (!file_exists($filePath)) {
            abort(404);
        }

        return response()->download($filePath, basename($transfer->file));
    }

    public function completeOrder(Request $request, $id)
    {
        $order = objModel::find($id);
        if ($order) {
            return jsonSuccess($order);
        }
        return jsonError(['error' => 'Order not Found!']);
    }

    public function getOrderById(Request $request, $id)
    {
        $order = objModel::with('user')->where('id', $id)->first();
        if ($order) {
            return jsonSuccess($order);
        }
        return jsonError(['error' => 'Order not Found!']);
    }

    public function changeMaid(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'maid_id' => 'required|exists:maids,id'
            ]);

            $order = Order::findOrFail($request->order_id);
            $maid = \App\Models\ingaz\Maid::findOrFail($request->maid_id);

            // Check if maid is available and not reserved
            if (!$maid->available || $maid->status !== 'متاحة') {
                return jsonError(['message' => 'السيرة الذاتية غير متاحة أو محجوزة']);
            }

            // Check if maid is already assigned to another active order
            $existingOrder = Order::where('maid_id', $request->maid_id)
                ->where('id', '!=', $request->order_id)
                ->whereNotIn('status', ['completed', 'refused', 'done'])
                ->first();

            if ($existingOrder) {
                return jsonError(['message' => 'السيرة الذاتية محجوزة لطلب آخر']);
            }

            // Update order with new maid
            $order->maid_id = $request->maid_id;
            $order->save();

            return jsonSuccess(['message' => 'تم تغيير السيرة الذاتية بنجاح']);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تغيير السيرة الذاتية']);
        }
    }

        public function changeResponsible(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'admin_id' => 'required|exists:admins,id'
            ]);

            $order = Order::findOrFail($request->order_id);
            $admin = \App\Models\auth\Admin::findOrFail($request->admin_id);

            // Check if admin is customer service
            if ($admin->admin_type !== 'customer_service') {
                return jsonError(['message' => 'يمكن فقط اختيار موظف خدمة العملاء']);
            }

            // Update or create order details with new admin
            $orderDetail = $order->details;
            if (!$orderDetail) {
                $orderDetail = new \App\Models\ingaz\OrderDetail();
                $orderDetail->order_id = $order->id;
            }

            $orderDetail->admin_id = $request->admin_id;
            $orderDetail->save();

            return jsonSuccess(['message' => 'تم تغيير مسئول الطلب بنجاح']);
        } catch (\Exception $e) {
            return jsonError(['message' => 'حدث خطأ أثناء تغيير مسئول الطلب']);
        }
    }

    public function exportPdf()
    {
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')->get();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة طلبات الاستقدام</title>
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
                .status-active {
                    color: green;
                }
                .status-blocked {
                    color: red;
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
                <h1>قائمة طلبات الاستقدام</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم الطلب</th>
                        <th>كود الطلب</th>
                        <th>العميل</th>
                        <th>رقم هاتف العميل</th>
                        <th>العاملة</th>
                        <th>رقم جواز العامل</th>
                        <th>نوع الوظيفة</th>
                        <th>طريقة الدفع</th>
                        <th>الحالة</th>
                        <th>تاريخ الإنشاء</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($orders as $order) {
            $statusText = getEnumValue('orders_statuses', $order->status);
            $html .= '<tr>
                <td>' . $order->id . '</td>
                <td>' . $order->order_code . '</td>
                <td>' . ($order->user ? $order->user->name : '') . '</td>
                <td>' . ($order->user ? getPhoneWithTag($order->user->phone_code . $order->user->phone) : '') . '</td>
                <td>' . ($order->maid ? $order->maid->name : '') . '</td>
                <td>' . ($order->maid ? $order->maid->passport_num : '') . '</td>
                <td>' . ($order->jobType ? $order->jobType->title : '') . '</td>
                <td>' . $order->payment_method . '</td>
                <td>' . $statusText . '</td>
                <td>' . $order->created_at->format('Y-m-d H:i A') . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="orders.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        $orders = objModel::with('user', 'maid', 'jobType', 'details.admin')->get();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم الطلب',
            'كود الطلب',
            'العميل',
            'رقم هاتف العميل',
            'العاملة',
            'رقم جواز العامل',
            'نوع الوظيفة',
            'طريقة الدفع',
            'الحالة',
            'تاريخ الإنشاء'
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
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($orders as $order) {
            $statusText = getEnumValue('orders_statuses', $order->status);
            $sheet->setCellValue('A' . $row, $order->id);
            $sheet->setCellValue('B' . $row, $order->order_code);
            $sheet->setCellValue('C' . $row, $order->user ? $order->user->name : '');
            $sheet->setCellValue('D' . $row, $order->user ? $order->user->phone_code . $order->user->phone : '');
            $sheet->setCellValue('E' . $row, $order->maid ? $order->maid->name : '');
            $sheet->setCellValue('F' . $row, $order->maid ? $order->maid->passport_num : '');
            $sheet->setCellValue('G' . $row, $order->jobType ? $order->jobType->title : '');
            $sheet->setCellValue('H' . $row, $order->payment_method);
            $sheet->setCellValue('I' . $row, $statusText);
            $sheet->setCellValue('J' . $row, $order->created_at->format('Y-m-d H:i A'));
            $row++;
        }

        // Auto-size columns
        foreach(range('A','J') as $column) {
            $sheet->getColumnDimension($column)->setAutoSize(true);
        }

        // Create Excel writer
        $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        // Set headers for download
        $headers = [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename="orders.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'orders.xlsx', $headers)->deleteFileAfterSend(true);
    }
}
