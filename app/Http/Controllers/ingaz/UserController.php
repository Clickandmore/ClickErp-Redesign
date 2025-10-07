<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Actions\ingaz\UserAction as objAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\User as objModel;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;
use Spatie\Activitylog\Models\Activity;
use App\Keys;

class UserController extends Controller
{

    public $folderPath = "admin.ingaz.clients";
    public $postData = ["is_blocked"];
    public $mainRoute = "clients";

    function __construct()
    {
        // $this->middleware('permission:العملاء-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:العملاء-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:العملاء-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:العملاء-حذف,admin', ['only' => ['destroy']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request, objAction $Action)
    {
        if ($request->ajax()) {
            $units = objModel::query()->orderBy("id", "desc");

            return DataTables::of($units)
                ->addIndexColumn()
                ->editColumn('is_blocked', function ($row) {
                    return
                        $row->is_blocked == 0 ? '<span class="badge bg-success">مفعل</span>' :
                            '<span class="badge bg-danger">محظور</span>';
                })
                ->editColumn('phone', function ($row) {
                    return getPhoneWithTag($row->phone_code.$row->phone);
                })
                ->editColumn('created_at', function ($row) {
                    return (isset($row->created_at)) ? $row->created_at->diffForHumans() : '-';
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '';

                    // Add recruitment request button first
                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE_RECRUITMENT)) {
                        $buttons .= '<a href="' . route('admin.recruitment.select.maid', ['client_id' => $row->id]) . '" class="btn btn-sm btn-success me-1">' . __('ingaz.create_recruitment') . '</a> ';
                    }

                    // Show client details
                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
                        $buttons .= '<a href="' . route($this->mainRoute . '.show', $row->id) . '" class="btn btn-sm btn-info me-1" title="عرض التفاصيل وطلبات نقل الكفالة">' . __('ingaz.show') . '</a> ';
                    }

                    // Edit client
                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
                        $buttons .= '<a href="' . route($this->mainRoute . '.edit', $row->id) . '" class="btn btn-sm btn-primary me-1">' . __('ingaz.edit') . '</a> ';
                    }

                    // Delete client
                    if (@AdminGuardHasPerm(\App\PermKeys::CLIENTS_DELETE)) {
                        $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    }

                    return $buttons;
                })
                ->rawColumns(['actions', 'is_blocked', 'phone'])
                ->make(true);
        }
        $data["createRoute"] = route($this->mainRoute . ".create");
        $data["dataTableRoute"] = route($this->mainRoute . ".index");
        $data["bladeTitle"] = __("auth.clients");
        $data["addButtonText"] = __("auth.clients");
        $data["modalType"] = "";
        return view($this->folderPath . '.index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE)) {
            abort(403, 'ليس لديك صلاحية لإضافة عميل جديد');
        }

        $countries = \App\Models\Country::active()->ordered()->get();
        return view($this->folderPath . '.create', compact('countries'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request, objAction $action)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_CREATE)) {
            return response()->json([
                'code' => 403,
                'message' => 'ليس لديك صلاحية لإضافة عميل جديد'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone' => 'required|string|max:20|unique:users,phone',
            'email' => 'nullable|email|max:255|unique:users,email',
            'password' => 'required|string|min:6',
            'nationality_id' => 'nullable|exists:countries,id',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100'
        ], [
            'name.required' => __('ingaz.client_name_required'),
            'name.string' => __('ingaz.client_name_required'),
            'name.max' => __('ingaz.client_name_max'),
            'phone.required' => __('ingaz.client_phone_required'),
            'phone.string' => __('ingaz.client_phone_required'),
            'phone.max' => __('ingaz.client_phone_max'),
            'phone.unique' => __('ingaz.client_phone_already_exists'),
            'email.email' => __('ingaz.client_email_invalid'),
            'email.max' => __('ingaz.client_email_max'),
            'email.unique' => __('ingaz.client_email_already_exists'),
            'password.required' => __('ingaz.client_password_required'),
            'password.string' => __('ingaz.client_password_required'),
            'password.min' => __('ingaz.client_password_min'),
            'nationality_id.exists' => __('ingaz.client_nationality_not_found'),
            'national_num.string' => __('ingaz.client_national_num_max'),
            'national_num.max' => __('ingaz.client_national_num_max'),
            'birthdate.date' => __('ingaz.client_birthdate_invalid'),
            'phone2.string' => __('ingaz.client_phone2_max'),
            'phone2.max' => __('ingaz.client_phone2_max'),
            'address.string' => __('ingaz.client_address_max'),
            'address.max' => __('ingaz.client_address_max'),
            'city.string' => __('ingaz.client_city_max'),
            'city.max' => __('ingaz.client_city_max'),
            'rooms_num.integer' => __('ingaz.client_rooms_num_integer'),
            'rooms_num.min' => __('ingaz.client_rooms_num_min'),
            'family_num.integer' => __('ingaz.client_family_num_integer'),
            'family_num.min' => __('ingaz.client_family_num_min'),
            'children.integer' => __('ingaz.client_children_integer'),
            'children.min' => __('ingaz.client_children_min'),
            'job.string' => __('ingaz.client_job_max'),
            'job.max' => __('ingaz.client_job_max')
        ]);

        $data['password'] = bcrypt($data['password']);
        $data['is_blocked'] = 0;

        $client = objModel::create($data);

        return response()->json([
            'code' => 200,
            'message' => __('ingaz.client_created_successfully'),
            'url' => route($this->mainRoute . '.index')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_SHOW)) {
            abort(403, 'ليس لديك صلاحية لعرض تفاصيل العميل');
        }

        // Mahmoud Amer's code (Used eager loading)
        $client = objModel::with(['activities','messages','complaints', 'orders'])->findOrFail($id);

        // جلب طلبات نقل الكفالة للعميل
        $sponsorshipTransfers = \App\Models\SponsorshipTransfer::with([
            'contract',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy'
        ])->where(function($query) use ($client) {
            $query->where('old_sponsor_id', $client->id)
                  ->orWhere('new_sponsor_id', $client->id);
        })->orderBy('created_at', 'desc')->get();

        // Mahmoud Amer's code
        // logger()->info('Show Data from DB', [
        //     'client' => $client ?? 'null',
        //     'activity_logs' => $client->activities ?? 'null',
        //     'messages' => $client->messages ?? 'null',
        //     'complaints' => $client->complaints ?? 'null',
        //     'orders' => $client->orders ?? 'null',
        // ]);
        return view($this->folderPath . '.show', compact('client', 'sponsorshipTransfers'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            abort(403, 'ليس لديك صلاحية لتعديل العميل');
        }

        $client = objModel::findOrFail($id);
        $countries = \App\Models\Country::active()->ordered()->get();
        return view($this->folderPath . '.edit', compact('client', 'countries'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EDIT)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لتعديل العميل'
            ], 403);
        }

        $client = objModel::findOrFail($id);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'phone_code' => 'required|string|max:10',
            'phone' => 'required|string|max:20|unique:users,phone,' . $client->id,
            'email' => 'nullable|email|max:255|unique:users,email,' . $client->id,
            'nationality_id' => 'nullable|exists:countries,id',
            'national_num' => 'nullable|string|max:50',
            'birthdate' => 'nullable|date',
            'phone2' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'rooms_num' => 'nullable|integer|min:0',
            'family_num' => 'nullable|integer|min:0',
            'children' => 'nullable|integer|min:0',
            'job' => 'nullable|string|max:100',
            'is_blocked' => 'required|boolean'
        ], [
            'name.required' => __('ingaz.client_name_required'),
            'name.string' => __('ingaz.client_name_required'),
            'name.max' => __('ingaz.client_name_max'),
            'phone_code.required' => __('ingaz.client_phone_code_required'),
            'phone_code.string' => __('ingaz.client_phone_code_required'),
            'phone_code.max' => __('ingaz.client_phone_code_max'),
            'phone.required' => __('ingaz.client_phone_required'),
            'phone.string' => __('ingaz.client_phone_required'),
            'phone.max' => __('ingaz.client_phone_max'),
            'phone.unique' => __('ingaz.client_phone_already_exists'),
            'email.email' => __('ingaz.client_email_invalid'),
            'email.max' => __('ingaz.client_email_max'),
            'email.unique' => __('ingaz.client_email_already_exists'),
            'nationality_id.exists' => __('ingaz.client_nationality_not_found'),
            'national_num.string' => __('ingaz.client_national_num_max'),
            'national_num.max' => __('ingaz.client_national_num_max'),
            'birthdate.date' => __('ingaz.client_birthdate_invalid'),
            'phone2.string' => __('ingaz.client_phone2_max'),
            'phone2.max' => __('ingaz.client_phone2_max'),
            'address.string' => __('ingaz.client_address_max'),
            'address.max' => __('ingaz.client_address_max'),
            'city.string' => __('ingaz.client_city_max'),
            'city.max' => __('ingaz.client_city_max'),
            'rooms_num.integer' => __('ingaz.client_rooms_num_integer'),
            'rooms_num.min' => __('ingaz.client_rooms_num_min'),
            'family_num.integer' => __('ingaz.client_family_num_integer'),
            'family_num.min' => __('ingaz.client_family_num_min'),
            'children.integer' => __('ingaz.client_children_integer'),
            'children.min' => __('ingaz.client_children_min'),
            'job.string' => __('ingaz.client_job_max'),
            'job.max' => __('ingaz.client_job_max'),
            'is_blocked.required' => __('ingaz.client_is_blocked_required'),
            'is_blocked.boolean' => __('ingaz.client_is_blocked_boolean')
        ]);

        $client->update($data);

        return jsonSuccess(null, null, 201);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, objAction $action)
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_DELETE)) {
            return response()->json([
                'success' => false,
                'message' => 'ليس لديك صلاحية لحذف العميل'
            ], 403);
        }

        $action->delete($id);
        return jsonSuccess();
    }

    public function exportPdf()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_PDF)) {
            abort(403, 'ليس لديك صلاحية لتصدير بيانات العملاء بصيغة PDF');
        }

        $clients = objModel::all();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة العملاء</title>
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
                <h1>قائمة العملاء</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العميل</th>
                        <th>الاسم</th>
                        <th>رقم الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنسية</th>
                        <th>رقم الهوية</th>
                        <th>تاريخ الميلاد</th>
                        <th>العنوان</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($clients as $client) {
            $statusClass = $client->is_blocked ? 'status-blocked' : 'status-active';
            $statusText = $client->is_blocked ? 'محظور' : 'مفعل';

            $html .= '<tr>
                <td>' . $client->id . '</td>
                <td>' . $client->name . '</td>
                <td>' . $client->phone_code . $client->phone . '</td>
                <td>' . $client->email . '</td>
                <td>' . ($client->nationality->title ?? '') . '</td>
                <td>' . $client->national_num . '</td>
                <td>' . $client->birthdate . '</td>
                <td>' . $client->address . '</td>
                <td>' . $client->city . '</td>
                <td class="' . $statusClass . '">' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="clients.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportPdfNewWindow()
    {
        $clients = objModel::all();

        // Create HTML content with RTL support and better styling
        $html = '<!DOCTYPE html>
        <html dir="rtl">
        <head>
            <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
            <title>قائمة العملاء</title>
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
                <h1>قائمة العملاء</h1>
                <p>تم التصدير في: ' . date('Y-m-d H:i:s') . '</p>
                <button class="print-button" onclick="printDocument()">طباعة</button>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>رقم العميل</th>
                        <th>الاسم</th>
                        <th>رقم الهاتف</th>
                        <th>البريد الإلكتروني</th>
                        <th>الجنسية</th>
                        <th>رقم الهوية</th>
                        <th>تاريخ الميلاد</th>
                        <th>العنوان</th>
                        <th>المدينة</th>
                        <th>الحالة</th>
                    </tr>
                </thead>
                <tbody>';

        foreach ($clients as $client) {
            $statusClass = $client->is_blocked ? 'status-blocked' : 'status-active';
            $statusText = $client->is_blocked ? 'محظور' : 'مفعل';

            $html .= '<tr>
                <td>' . $client->id . '</td>
                <td>' . $client->name . '</td>
                <td>' . $client->phone_code . $client->phone . '</td>
                <td>' . $client->email . '</td>
                <td>' . ($client->nationality->title ?? '') . '</td>
                <td>' . $client->national_num . '</td>
                <td>' . $client->birthdate . '</td>
                <td>' . $client->address . '</td>
                <td>' . $client->city . '</td>
                <td class="' . $statusClass . '">' . $statusText . '</td>
            </tr>';
        }

        $html .= '</tbody></table></body></html>';

        // Return HTML with print styles
        $headers = [
            'Content-Type' => 'text/html',
            'Content-Disposition' => 'inline; filename="clients.html"',
        ];

        return response($html, 200, $headers);
    }

    public function exportExcel()
    {
        if (!@AdminGuardHasPerm(\App\PermKeys::CLIENTS_EXPORT_EXCEL)) {
            abort(403, 'ليس لديك صلاحية لتصدير بيانات العملاء بصيغة Excel');
        }

        $clients = objModel::all();

        // Create new Spreadsheet object
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Set RTL direction
        $sheet->setRightToLeft(true);

        // Set headers
        $headers = [
            'رقم العميل',
            'الاسم',
            'رقم الهاتف',
            'البريد الإلكتروني',
            'الجنسية',
            'رقم الهوية',
            'تاريخ الميلاد',
            'العنوان',
            'المدينة',
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
        $sheet->getStyle('A1:J1')->applyFromArray($headerStyle);

        // Set data
        $row = 2;
        foreach ($clients as $client) {
            $sheet->setCellValue('A' . $row, $client->id);
            $sheet->setCellValue('B' . $row, $client->name);
            $sheet->setCellValue('C' . $row, $client->phone_code . $client->phone);
            $sheet->setCellValue('D' . $row, $client->email);
            $sheet->setCellValue('E' . $row, $client->nationality->title ?? '');
            $sheet->setCellValue('F' . $row, $client->national_num);
            $sheet->setCellValue('G' . $row, $client->birthdate);
            $sheet->setCellValue('H' . $row, $client->address);
            $sheet->setCellValue('I' . $row, $client->city);
            $sheet->setCellValue('J' . $row, $client->is_blocked ? 'محظور' : 'مفعل');
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
            'Content-Disposition' => 'attachment; filename="clients.xlsx"',
        ];

        // Save to temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'xlsx');
        $writer->save($tempFile);

        // Return file
        return response()->download($tempFile, 'clients.xlsx', $headers)->deleteFileAfterSend(true);
    }
}
