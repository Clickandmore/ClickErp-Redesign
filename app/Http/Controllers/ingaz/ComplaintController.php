<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\ingaz\Complaint as objModel;
use App\Models\ingaz\ComplaintAttachment;
use App\Models\ingaz\ComplaintComment;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\PermKeys;
use App\Services\AdminNotificationService;
use App\Services\ManagedFileStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class ComplaintController extends Controller
{
    use SendSMSTrait, StoreMessageTrait;

    public $folderPath = "admin.ingaz.complaints";
    public $mainRoute = "complaints";

    function __construct()
    {
        $this->middleware('permission:' . PermKeys::COMPLAINT_VIEW . '|' . PermKeys::COMPLAINT_CONTROL . ',admin', ['only' => ['cards']]);
        // $this->middleware('permission:الشكاوى-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الشكاوى-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الشكاوى-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الشكاوى-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->input('export') === 'excel') {
            return $this->exportComplaints($request);
        }

        if ($request->ajax()) {
            $query = objModel::query()->with(['user', 'complaintType', 'assignedTo', 'maidNationality', 'contract']);
            $this->applyRequestBranchFilter($query, $request, 'complaints');
            $this->applyComplaintFilters($query, $request);

            // حساب الإحصائيات
            $statsQuery = $this->applyRequestBranchFilter(objModel::query(), $request, 'complaints');
            $statistics = [
                'total' => (clone $statsQuery)->count(),
                'pending' => (clone $statsQuery)->where('status', 'pending')->count(),
                'in_progress' => (clone $statsQuery)->where('status', 'in_progress')->count(),
                'resolved' => (clone $statsQuery)->where('status', 'resolved')->count()
            ];

            return DataTables::of($query)
                ->with('statistics', $statistics)
                ->addIndexColumn()
                ->editColumn('user_id', function ($row) {
                    return $row->user ? $row->user->name : 'غير معروف';
                })
                ->editColumn('status', function ($row) {
                    $status = $row->status;
                    $badgeClass = '';
                    $statusText = '';
                    switch ($status) {
                        case 'pending':
                            $badgeClass = 'text-bg-warning';
                            $statusText = 'قيد الانتظار';
                            break;
                        case 'in_progress':
                            $badgeClass = 'text-bg-info';
                            $statusText = 'قيد المعالجة';
                            break;
                        case 'resolved':
                            $badgeClass = 'text-bg-success';
                            $statusText = 'تم الحل';
                            break;
                        case 'rejected':
                            $badgeClass = 'text-bg-danger';
                            $statusText = 'مرفوض';
                            break;
                    }
                    return '<span class="badge ' . $badgeClass . '">' . $statusText . '</span>';
                })
                ->editColumn('file', function ($row) {
                    return $row->file ? showFile($row->file) : null;
                })
                ->editColumn('user_id', function ($row) {
                    return $row->user ? @$row->user->name : null;
                })
                ->editColumn('response', function ($row) {
                    return $row->response ?? '-';
                })
                ->addColumn('assignedTo', function ($row) {
                    return $row->assignedTo ? [
                        'id' => $row->assignedTo->id,
                        'name' => $row->assignedTo->name
                    ] : null;
                })
                ->addColumn('complaintType', function ($row) {
                    return $row->complaintType ? [
                        'id' => $row->complaintType->id,
                        'name' => $row->complaintType->name
                    ] : null;
                })
                ->addColumn('contract', function ($row) {
                    return $row->contract ? [
                        'id' => $row->contract->id,
                        'url' => route('ShelterContract.show', $row->contract->id),
                    ] : null;
                })
                ->addColumn('actions', function ($row) {
                    $buttons = '<div class="btn-group" role="group" aria-label="Actions">';
                    $buttons .= '<button class="btn btn-sm btn-primary response-btn" data-id="' . $row->id . '" data-response="' . htmlspecialchars($row->response ?? '', ENT_QUOTES) . '" data-status="' . $row->status . '">
                        <i class="fa fa-reply"></i> ' . helperTrans('ingaz.respond') . '
                    </button>';
                    $buttons .= deleteButton(route($this->mainRoute . ".destroy", $row->id));
                    $buttons .= '</div>';
                    return $buttons;
                })
                ->filter(function ($query) {
                    if (request()->has('search') && $search = request('search.value')) {
                        $query->where(function ($q) use ($search) {
                            $q->orWhereHas('user', function ($uq) use ($search) {
                                $uq->where('name', 'LIKE', "%{$search}%");
                            })
                                ->orWhere('subject', 'LIKE', "%{$search}%")
                                ->orWhere('complainant_name', 'LIKE', "%{$search}%")
                                ->orWhere('priority', 'LIKE', "%{$search}%")
                                ->orWhere('status', 'LIKE', "%{$search}%")
                                ->orWhereHas('complaintType', function ($qq) use ($search) {
                                    $qq->where('name', 'LIKE', "%{$search}%");
                                })
                                ->orWhereHas('assignedTo', function ($qq) use ($search) {
                                    $qq->where('name', 'LIKE', "%{$search}%");
                                });
                        });
                    }
                })
                ->rawColumns(['status', 'actions', 'response', 'phone'])
                ->make(true);
        }

        // جلب البيانات للفلاتر
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();

        return view($this->folderPath . '.index', compact('complaintTypes', 'admins'));
    }

    private function exportComplaints(Request $request)
    {
        $query = objModel::query()->with(['complaintType', 'assignedTo']);
        $this->applyRequestBranchFilter($query, $request, 'complaints');
        $this->applyComplaintFilters($query, $request);
        $complaints = $query->latest('id')->get();

        $statuses = [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة',
            'rejected' => 'مرفوضة',
        ];
        $priorities = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية', 'urgent' => 'عاجلة'];

        return response()->streamDownload(function () use ($complaints, $statuses, $priorities) {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, ['رقم الشكوى', 'مقدم الشكوى', 'المشتكى عليه', 'الموضوع', 'النوع', 'الأولوية', 'الحالة', 'الموظف المختص', 'التاريخ']);

            foreach ($complaints as $complaint) {
                fputcsv($output, [
                    $complaint->id,
                    $complaint->complainant_name,
                    $complaint->against_name,
                    $complaint->subject,
                    $complaint->complaintType?->name,
                    $priorities[$complaint->priority] ?? $complaint->priority,
                    $statuses[$complaint->status] ?? $complaint->status,
                    $complaint->assignedTo?->name,
                    $complaint->created_at?->format('Y-m-d H:i'),
                ]);
            }

            fclose($output);
        }, 'complaints-' . now()->format('Y-m-d') . '.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function create()
    {
        $users = User::orderBy('name')->get(['id', 'name', 'phone']);
        $nationalities = Nationality::with('translations')->get()->sortBy('title');
        $complaintTypes = ComplaintType::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();
        $orders = Order::query()->with(['user', 'maid'])->latest('id')->limit(1000)->get(['id', 'order_code', 'user_id', 'maid_id', 'status']);
        $contracts = Contract::query()->with(['user', 'maid'])->latest('id')->limit(1000)->get(['id', 'user_id', 'maid_id', 'order_id', 'status']);
        $recruitmentOffices = RecruitmentOffices::query()->with('translations')->orderBy('id')->get();
        $partyTypes = ComplaintType::partyTypes();

        return view($this->folderPath . '.create', compact(
            'users', 'nationalities', 'complaintTypes', 'admins', 'orders', 'contracts',
            'recruitmentOffices', 'partyTypes'
        ));
    }

    public function store(Request $request, UserAction $userAction)
    {
        try {
            $request->validate([
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => [
                    'required',
                    'regex:/^(05|5)[0-9]{8}$/',
                ],
                'complainant_type' => 'required|in:client,worker,internal_office,external_office,employee,other',
                'subject' => 'required|string|max:255',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'details' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'user_id' => 'nullable|exists:users,id',
                'maid_passport' => 'nullable|string|max:50',
                'maid_nationality_id' => 'nullable|exists:nationalities,id',
                'assigned_to' => 'nullable|exists:admins,id',
                'relation_type' => 'required|in:none,order,contract,office,client,manual',
                'order_id' => 'nullable|required_if:relation_type,order|exists:orders,id',
                'contract_id' => 'nullable|required_if:relation_type,contract|exists:contracts,id',
                'recruitment_office_id' => 'nullable|required_if:relation_type,office|exists:recruitment_offices,id',
                'related_user_id' => 'nullable|required_if:relation_type,client|exists:users,id',
                'against_name' => 'nullable|required_if:relation_type,manual|string|max:255',
                'against_phone' => 'nullable|string|max:50',
                'relation_reference' => 'nullable|string|max:255',
                'relation_notes' => 'nullable|string|max:2000',
                'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'
            ]);

            $data = $request->except('attachments', 'attachment_names');
            $data['status'] = 'pending';
            $data['message'] = $request->details; // للتوافق مع الحقل القديم
            $rawPhone = $data['complainant_phone'] ?? '';
            $normalizedPhone = ltrim($rawPhone, '0');
            $data['complainant_phone'] = $normalizedPhone;

            $user = null;
            if (!empty($data['user_id'])) {
                $user = User::find($data['user_id']);
            }
            if (!$user) {
                $user = User::where('phone', $normalizedPhone)
                    ->orWhere('phone', $rawPhone)
                    ->first();
            }
            $data['user_id'] = $user?->id;
            $complaintType = ComplaintType::findOrFail($data['complaint_type_id']);
            $data['complainant_type'] = $complaintType->complainant_type ?: $data['complainant_type'];
            $data['against_type'] = $complaintType->against_type ?: ($data['against_type'] ?? null);
            $this->applyComplaintRelation($data);
            $complaint = objModel::create($data);
            $this->storeComplaintAttachments($complaint, $request->file('attachments', []));

            // إرسال إشعار للموظف المختص إذا تم تحديده
            if ($complaint->assigned_to) {
                // يمكن إضافة إشعار هنا
            }

            // إرجاع استجابة JSON للـ AJAX
            if ($request->ajax()) {
                return response()->json([
                    'code' => 201,
                    'message' => 'تم إضافة الشكوى بنجاح',
                    'data' => [
                        'url' => route($this->mainRoute . '.index')
                    ]
                ]);
            }

            return redirect()->route($this->mainRoute . '.index')
                ->with('success', 'تم إضافة الشكوى بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'يوجد أخطاء في البيانات المدخلة',
                    'data' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'code' => 500,
                    'message' => 'حدث خطأ أثناء حفظ الشكوى: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'حدث خطأ أثناء حفظ الشكوى')->withInput();
        }
    }

    private function applyComplaintRelation(array &$data): void
    {
        $relationType = $data['relation_type'] ?? 'none';
        $data['order_id'] = $relationType === 'order' ? ($data['order_id'] ?? null) : null;
        $data['contract_id'] = $relationType === 'contract' ? ($data['contract_id'] ?? null) : null;
        $data['recruitment_office_id'] = $relationType === 'office' ? ($data['recruitment_office_id'] ?? null) : null;
        $data['related_user_id'] = $relationType === 'client' ? ($data['related_user_id'] ?? null) : null;

        if ($relationType === 'order') {
            $order = Order::with(['user', 'maid', 'contracts.maid'])->findOrFail($data['order_id']);
            $this->applyResolvedAgainstParty($data, $this->resolveOrderAgainstParty($order, $data['against_type'] ?? null));
            $data['relation_reference'] = ($data['relation_reference'] ?? null) ?: ($order->order_code ?: (string) $order->id);
        } elseif ($relationType === 'contract') {
            $contract = Contract::with(['user', 'maid'])->findOrFail($data['contract_id']);
            $this->applyResolvedAgainstParty($data, $this->resolveContractAgainstParty($contract, $data['against_type'] ?? null));
            $data['relation_reference'] = ($data['relation_reference'] ?? null) ?: (string) $contract->id;
        } elseif ($relationType === 'office') {
            $office = RecruitmentOffices::findOrFail($data['recruitment_office_id']);
            $data['against_name'] = $office->title;
            $data['against_phone'] = $office->phone_office ?: ($data['against_phone'] ?? null);
            $data['relation_reference'] = ($data['relation_reference'] ?? null) ?: (string) $office->id;
        } elseif ($relationType === 'client') {
            $relatedUser = User::findOrFail($data['related_user_id']);
            $data['against_name'] = $relatedUser->name;
            $data['against_phone'] = $relatedUser->phone;
            $data['relation_reference'] = ($data['relation_reference'] ?? null) ?: (string) $relatedUser->id;
        } elseif ($relationType === 'none') {
            $data['against_name'] = null;
            $data['against_phone'] = null;
            $data['relation_reference'] = null;
            $data['relation_notes'] = null;
        } else {
            $data['against_type'] = $data['against_type'] ?? 'other';
        }

        $labels = ['order' => 'طلب', 'contract' => 'عقد', 'office' => 'مكتب خارجي', 'client' => 'عميل', 'manual' => 'بيانات يدوية'];
        $data['related_to'] = isset($labels[$relationType])
            ? $labels[$relationType] . (!empty($data['relation_reference']) ? ' #' . $data['relation_reference'] : '')
            : null;
    }

    private function resolveOrderAgainstParty(Order $order, ?string $againstType): array
    {
        if ($againstType !== 'worker') {
            return [
                'name' => $order->user?->name,
                'phone' => $order->user?->phone,
            ];
        }

        $contract = $order->contracts->first(function (Contract $contract) {
            return $contract->maid || filled($contract->info?->manual_maid_name);
        });
        $maid = $order->maid ?: $contract?->maid;

        return [
            'name' => $maid?->name ?: $contract?->info?->manual_maid_name,
            'phone' => null,
            'maid_passport' => $maid?->passport_num ?: $contract?->info?->manual_maid_passport_num,
            'maid_nationality_id' => $maid?->nationality_id ?: $contract?->info?->manual_maid_nationality_id,
        ];
    }

    private function resolveContractAgainstParty(Contract $contract, ?string $againstType): array
    {
        if ($againstType !== 'worker') {
            return [
                'name' => $contract->user?->name,
                'phone' => $contract->user?->phone,
            ];
        }

        return [
            'name' => $contract->maid?->name ?: $contract->info?->manual_maid_name,
            'phone' => null,
            'maid_passport' => $contract->maid?->passport_num ?: $contract->info?->manual_maid_passport_num,
            'maid_nationality_id' => $contract->maid?->nationality_id ?: $contract->info?->manual_maid_nationality_id,
        ];
    }

    private function applyResolvedAgainstParty(array &$data, array $party): void
    {
        $data['against_name'] = $party['name'] ?? null;
        $data['against_phone'] = $party['phone'] ?? null;

        if (empty($data['maid_passport']) && !empty($party['maid_passport'])) {
            $data['maid_passport'] = $party['maid_passport'];
        }
        if (empty($data['maid_nationality_id']) && !empty($party['maid_nationality_id'])) {
            $data['maid_nationality_id'] = $party['maid_nationality_id'];
        }
    }

    public function show($id)
    {
        $complaint = objModel::with([
            'user', 'complaintType', 'assignedTo', 'resolvedBy', 'maidNationality', 'contract.maid',
            'order.user', 'order.maid', 'order.contracts.maid', 'relatedUser', 'recruitmentOffice',
            'comments.admin', 'attachmentFiles.addedBy',
        ])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        $againstParty = [
            'name' => $complaint->against_name,
            'phone' => $complaint->against_phone,
        ];
        $againstType = $complaint->against_type ?: $complaint->complaintType?->against_type;
        if (!$againstType && preg_match('/عميل.*ضد.*عامل/u', (string) $complaint->complaintType?->name)) {
            $againstType = 'worker';
        }

        if ($againstType === 'worker') {
            $resolvedParty = $complaint->relation_type === 'contract' && $complaint->contract
                ? $this->resolveContractAgainstParty($complaint->contract, 'worker')
                : ($complaint->relation_type === 'order' && $complaint->order
                    ? $this->resolveOrderAgainstParty($complaint->order, 'worker')
                    : []);
            $againstParty['name'] = $resolvedParty['name'] ?? $againstParty['name'];
            $againstParty['phone'] = $resolvedParty['phone'] ?? null;
        }

        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();

        // إضافة شارات الحالة والأولوية
        $complaint->statusBadge = $this->getStatusBadge($complaint->status);
        $complaint->priorityBadge = $this->getPriorityBadge($complaint->priority);

        return view($this->folderPath . '.show', compact('complaint', 'admins', 'againstParty'));
    }

    public function edit($id)
    {
        $complaint = objModel::findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        $users = User::orderBy('name')->get();
        $nationalities = Nationality::with('translations')->get()->sortBy('title');
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();

        return view($this->folderPath . '.edit', compact('complaint', 'users', 'nationalities', 'complaintTypes', 'admins'));
    }

    public function update(Request $request, $id)
    {
        try {
            $complaint = objModel::findOrFail($id);
            $this->assertCanAccessBranchModel($complaint);

            $request->validate([
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => 'required|regex:/^05[0-9]{8}$/',
                'subject' => 'required|string|max:255',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'details' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'status' => 'required|in:pending,in_progress,resolved,closed,rejected',
                'user_id' => 'nullable|exists:users,id',
                'maid_passport' => 'nullable|string|max:50',
                'maid_nationality_id' => 'nullable|exists:nationalities,id',
                'assigned_to' => 'nullable|exists:admins,id',
                'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'
            ]);

            $data = $request->except('attachments', 'attachment_names', '_method');
            $data['message'] = $request->details; // للتوافق مع الحقل القديم

            // تحديث حالة الحل إذا تم تغيير الحالة
            if ($request->status == 'resolved' && $complaint->status != 'resolved') {
                $data['resolved_at'] = now();
                $data['resolved_by'] = auth()->guard('admin')->id();
            }

            $complaint->update($data);
            $this->storeComplaintAttachments($complaint, $request->file('attachments', []));

            // إرجاع استجابة JSON للـ AJAX
            if ($request->ajax()) {
                return response()->json([
                    'code' => 200,
                    'message' => 'تم تحديث الشكوى بنجاح',
                    'data' => [
                        'url' => route($this->mainRoute . '.index')
                    ]
                ]);
            }

            return redirect()->route($this->mainRoute . '.index')
                ->with('success', 'تم تحديث الشكوى بنجاح');
        } catch (\Illuminate\Validation\ValidationException $e) {
            if ($request->ajax()) {
                return response()->json([
                    'code' => 422,
                    'message' => 'يوجد أخطاء في البيانات المدخلة',
                    'data' => $e->errors()
                ], 422);
            }
            throw $e;
        } catch (\Exception $e) {
            if ($request->ajax()) {
                return response()->json([
                    'code' => 500,
                    'message' => 'حدث خطأ أثناء تحديث الشكوى: ' . $e->getMessage()
                ], 500);
            }
            return back()->with('error', 'حدث خطأ أثناء تحديث الشكوى')->withInput();
        }
    }

    public function cards(Request $request)
    {
        $query = objModel::with(['complaintType', 'assignedTo']);
        $this->applyRequestBranchFilter($query, $request, 'complaints');
        $this->applyComplaintFilters($query, $request);

        $complaints = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json(['data' => $complaints]);
    }

    private function applyComplaintFilters($query, Request $request): void
    {
        if ($request->filled('ticket_number')) {
            $ticketNumber = ltrim((string) $request->ticket_number, '#0');
            $query->where('id', 'LIKE', '%' . ($ticketNumber === '' ? 0 : $ticketNumber) . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority', $request->priority);
        }

        if ($request->filled('type')) {
            $query->where('complaint_type_id', $request->type);
        }

        if ($request->filled('assigned_to')) {
            $query->where('assigned_to', $request->assigned_to);
        }

        switch ($request->period) {
            case 'today':
                $query->whereDate('created_at', today());
                break;
            case 'yesterday':
                $query->whereDate('created_at', today()->subDay());
                break;
            case 'week':
                $query->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()]);
                break;
            case 'month':
                $query->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()]);
                break;
            case 'custom':
                if ($request->filled('date_from')) {
                    $query->whereDate('created_at', '>=', $request->date_from);
                }
                if ($request->filled('date_to')) {
                    $query->whereDate('created_at', '<=', $request->date_to);
                }
                break;
        }
    }

    public function quickResponse(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousStatus = $complaint->status;

        if ($request->response) {
            $complaint->response = $request->response;
        }

        if ($request->status) {
            $complaint->status = $request->status;

            if ($request->status == 'resolved') {
                $complaint->resolved_at = now();
                $complaint->resolved_by = auth('admin')->id();
            }
        }

        $complaint->save();
        $this->notifyComplaintStatusChanged($complaint, $previousStatus);

        if ($request->response && $previousStatus === $complaint->status) {
            $this->notifyComplaintUpdated($complaint, 'تم إضافة رد على الشكوى');
        }

        return response()->json(['success' => true, 'message' => 'تم إرسال الرد بنجاح']);
    }

    public function addComment(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousStatus = $complaint->status;
        $previousAssignedTo = $complaint->assigned_to;

        // إضافة التعليق إلى سجل التحديثات
        // يمكن إنشاء جدول منفصل للتعليقات إذا لزم الأمر

        if ($request->status) {
            $complaint->status = $request->status;
            // dd($request->status);
            if ($request->status == 'resolved') {
                $complaint->resolved_at = now();
                $complaint->resolved_by = auth('admin')->id();
            }
        }

        if ($request->assigned_to) {
            $complaint->assigned_to = $request->assigned_to;
        }

        $complaint->save();


        // Add Comment for this complant
        ComplaintComment::create([
            'comment' => $request->comment ?? '-',
            'complaint_id' => $request->complaint_id,
            'added_by' => auth()->user()->id,
            'attachments' => $request->attachments ?? null
        ]);

        $this->notifyComplaintStatusChanged($complaint, $previousStatus);
        $this->notifyComplaintAssignmentChanged($complaint, $previousAssignedTo);

        if ($previousStatus === $complaint->status && (int) $previousAssignedTo === (int) $complaint->assigned_to) {
            $this->notifyComplaintUpdated($complaint, 'تم إضافة تحديث على الشكوى');
        }

        return response()->json(['success' => true, 'message' => 'تم إضافة التحديث بنجاح']);
    }

    public function updateStatus(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousStatus = $complaint->status;
        $complaint->status = $request->status;

        if ($request->status == 'resolved') {
            $complaint->resolved_at = now();
            $complaint->resolved_by = auth('admin')->id();

            // إرسال رسالة التقييم إذا كان مفعلاً وهناك رقم هاتف
            $smsEnabled = env('SMS_FEEDBACK_ENABLED', false);
            if ($smsEnabled && $previousStatus != 'resolved' && $complaint->complainant_phone) {
                try {
                    \App\Http\Controllers\admin\ComplaintFeedbackController::sendFeedbackSMS($complaint);
                } catch (\Exception $e) {
                    \Log::error('Failed to send feedback SMS: ' . $e->getMessage());
                }
            }
        }

        $complaint->save();
        $this->notifyComplaintStatusChanged($complaint, $previousStatus);

        return response()->json(['success' => true, 'message' => 'تم تحديث الحالة بنجاح']);
    }

    public function assign(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousAssignedTo = $complaint->assigned_to;
        $complaint->assigned_to = $request->assigned_to;
        $complaint->save();
        $this->notifyComplaintAssignmentChanged($complaint, $previousAssignedTo);

        return response()->json(['success' => true, 'message' => 'تم تحويل التذكرة بنجاح']);
    }

    public function changePriority(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousPriority = $complaint->priority;
        $complaint->priority = $request->priority;
        $complaint->save();
        $this->notifyComplaintPriorityChanged($complaint, $previousPriority);

        return response()->json(['success' => true, 'message' => 'تم تغيير الأولوية بنجاح']);
    }

    public function addAttachments(Request $request, $complaint)
    {
        $complaint = objModel::findOrFail($complaint);
        $this->assertCanAccessBranchModel($complaint);

        $request->validate([
            'attachments' => ['required', 'array', 'min:1', 'max:10'],
            'attachments.*' => ['required', 'file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx'],
            'attachment_names' => ['nullable', 'array'],
            'attachment_names.*' => ['nullable', 'string', 'max:255'],
        ], [
            'attachments.required' => 'يرجى اختيار مرفق واحد على الأقل.',
            'attachments.*.max' => 'يجب ألا يتجاوز حجم المرفق 5 ميجابايت.',
            'attachments.*.mimes' => 'صيغة أحد المرفقات غير مدعومة.',
        ]);

        $this->storeComplaintAttachments(
            $complaint,
            $request->file('attachments', []),
            $request->input('attachment_names', [])
        );
        $this->notifyComplaintUpdated($complaint, 'تمت إضافة مرفقات إلى الشكوى');

        return back()->with('success', 'تمت إضافة المرفقات بنجاح.');
    }

    public function deleteAttachment($complaint, int $attachment)
    {
        $complaint = objModel::findOrFail($complaint);
        $this->assertCanAccessBranchModel($complaint);
        $attachment = $complaint->attachmentFiles()->whereKey($attachment)->firstOrFail();

        deleteFile($attachment->path);
        $attachment->delete();

        return back()->with('success', 'تم حذف المرفق بنجاح.');
    }

    private function storeComplaintAttachments(objModel $complaint, array $files, array $names = []): void
    {
        foreach (array_values($files) as $index => $file) {
            $path = app(ManagedFileStorage::class)->uploadFile($file, 'complaints/attachments');

            try {
                $complaint->attachmentFiles()->create([
                    'path' => $path,
                    'display_name' => trim((string) ($names[$index] ?? '')) ?: null,
                    'original_name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'added_by' => auth('admin')->id(),
                ]);
            } catch (\Throwable $exception) {
                deleteFile($path);
                throw $exception;
            }
        }
    }

    public function destroy($id)
    {
        $complaint = objModel::findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        foreach ($complaint->attachmentFiles as $attachment) {
            deleteFile($attachment->path);
        }

        // حذف المرفقات القديمة قبل نقلها إلى الجدول الجديد
        if ($complaint->attachments && is_array($complaint->attachments)) {
            foreach ($complaint->attachments as $attachment) {
                deleteFile($attachment);
            }
        }

        $complaint->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الشكوى بنجاح']);
    }

    private function getStatusBadge($status)
    {
        $badges = [
            'pending' => '<span class="badge text-bg-warning">قيد الانتظار</span>',
            'in_progress' => '<span class="badge text-bg-info">قيد المعالجة</span>',
            'resolved' => '<span class="badge text-bg-success">تم الحل</span>',
            'closed' => '<span class="badge text-bg-secondary">مغلقة</span>',
            'rejected' => '<span class="badge text-bg-danger">مرفوضة</span>'
        ];

        return $badges[$status] ?? '<span class="badge text-bg-light text-dark">' . $status . '</span>';
    }

    private function getPriorityBadge($priority)
    {
        $badges = [
            'urgent' => '<span class="badge text-bg-danger">عاجلة</span>',
            'high' => '<span class="badge text-bg-warning">عالية</span>',
            'medium' => '<span class="badge text-bg-info">متوسطة</span>',
            'low' => '<span class="badge text-bg-secondary">منخفضة</span>'
        ];

        return $badges[$priority] ?? '<span class="badge text-bg-light text-dark">' . $priority . '</span>';
    }

    private function notifyComplaintStatusChanged(objModel $complaint, ?string $previousStatus): void
    {
        if ((string) $previousStatus === (string) $complaint->status) {
            return;
        }

        $this->notificationService()->sendToAdmins(
            [$complaint->assigned_to],
            'تم تحديث حالة الشكوى #' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
            'تغيرت حالة الشكوى من "' . $this->complaintStatusLabel($previousStatus) . '" إلى "' . $this->complaintStatusLabel($complaint->status) . '".',
            'complaint_status_changed',
            route('complaints.show', $complaint->id),
            [
                'model' => objModel::class,
                'model_id' => $complaint->id,
                'old_status' => $previousStatus,
                'new_status' => $complaint->status,
            ]
        );
    }

    private function notifyComplaintAssignmentChanged(objModel $complaint, $previousAssignedTo): void
    {
        if ((int) $previousAssignedTo === (int) $complaint->assigned_to) {
            return;
        }

        $ticketNumber = str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT);
        $link = route('complaints.show', $complaint->id);

        $this->notificationService()->sendToAdmin(
            $complaint->assigned_to,
            'تم إسناد شكوى إليك #' . $ticketNumber,
            'تم تحويل الشكوى "' . ($complaint->subject ?: 'بدون عنوان') . '" إليك للمتابعة.',
            'complaint_assigned',
            $link,
            [
                'model' => objModel::class,
                'model_id' => $complaint->id,
                'old_assigned_to' => $previousAssignedTo,
                'new_assigned_to' => $complaint->assigned_to,
            ]
        );

        $this->notificationService()->sendToAdmin(
            $previousAssignedTo,
            'تم تحويل شكوى من عهدتك #' . $ticketNumber,
            'تم تحويل الشكوى "' . ($complaint->subject ?: 'بدون عنوان') . '" إلى موظف آخر.',
            'complaint_reassigned',
            $link,
            [
                'model' => objModel::class,
                'model_id' => $complaint->id,
                'old_assigned_to' => $previousAssignedTo,
                'new_assigned_to' => $complaint->assigned_to,
            ]
        );
    }

    private function notifyComplaintPriorityChanged(objModel $complaint, ?string $previousPriority): void
    {
        if ((string) $previousPriority === (string) $complaint->priority) {
            return;
        }

        $this->notificationService()->sendToAdmins(
            [$complaint->assigned_to],
            'تم تغيير أولوية الشكوى #' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
            'تغيرت أولوية الشكوى من "' . $this->complaintPriorityLabel($previousPriority) . '" إلى "' . $this->complaintPriorityLabel($complaint->priority) . '".',
            'complaint_priority_changed',
            route('complaints.show', $complaint->id),
            [
                'model' => objModel::class,
                'model_id' => $complaint->id,
                'old_priority' => $previousPriority,
                'new_priority' => $complaint->priority,
            ]
        );
    }

    private function notifyComplaintUpdated(objModel $complaint, string $title): void
    {
        $this->notificationService()->sendToAdmins(
            [$complaint->assigned_to],
            $title . ' #' . str_pad((string) $complaint->id, 6, '0', STR_PAD_LEFT),
            'تم تحديث الشكوى "' . ($complaint->subject ?: 'بدون عنوان') . '".',
            'complaint_updated',
            route('complaints.show', $complaint->id),
            [
                'model' => objModel::class,
                'model_id' => $complaint->id,
            ]
        );
    }

    private function complaintStatusLabel(?string $status): string
    {
        return [
            'pending' => 'قيد الانتظار',
            'in_progress' => 'قيد المعالجة',
            'resolved' => 'تم الحل',
            'closed' => 'مغلقة',
            'rejected' => 'مرفوضة',
        ][$status] ?? ($status ?: 'غير محدد');
    }

    private function complaintPriorityLabel(?string $priority): string
    {
        return [
            'urgent' => 'عاجلة',
            'high' => 'عالية',
            'medium' => 'متوسطة',
            'low' => 'منخفضة',
        ][$priority] ?? ($priority ?: 'غير محدد');
    }

    private function notificationService(): AdminNotificationService
    {
        return app(AdminNotificationService::class);
    }

    public function response(Request $request)
    {
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousStatus = $complaint->status;
        $complaint->response = $request->response;
        $complaint->status = $request->status;
        $complaint->save();
        $this->notifyComplaintStatusChanged($complaint, $previousStatus);
        if ($request->response && $previousStatus === $complaint->status) {
            $this->notifyComplaintUpdated($complaint, 'تم إضافة رد على الشكوى');
        }

        // Send SMS notification to the user
        if ($complaint->user && $complaint->user->phone) {
            $statusText = '';
            switch ($request->status) {
                case 'resolved':
                    $statusText = 'تم حل الشكوى';
                    break;
                case 'rejected':
                    $statusText = 'تم رفض الشكوى';
                    break;
                default:
                    $statusText = 'تم تحديث حالة الشكوى';
            }

            $message = "مرحباً {$complaint->user->name}\n";
            $message .= "{$statusText}\n";
            $message .= "الرد: {$request->response}";

            $result = $this->sendSMS(formatSaudiPhone($complaint->user->phone), $message);

            if (isset($result['code']) && $result['code'] == "1") {
                $this->storeMessage('ارسال رسالة نصية الى العميل', $message, NotificationActionEnum::SMS->value, $complaint->user->id);
            }
        }

        return jsonSuccess(null, 'تم حفظ الرد بنجاح');
    }


    public function deleteComment($id)
    {
        $complaint_comment = ComplaintComment::findOrFail($id);
        $this->assertCanAccessBranchModel($complaint_comment);

        abort_unless($this->canDeleteComment($complaint_comment), 403, __('ingaz.permission_denied'));

        $complaint_comment->delete();

        return jsonSuccess(['success' => 'تم حذف التعليق بنجاح']);
    }

    private function canDeleteComment(ComplaintComment $comment): bool
    {
        $admin = auth('admin')->user();

        return $admin
            && ((int) $admin->id === (int) $comment->added_by
                || AdminGuardHasPerm(PermKeys::COMPLAINT_CONTROL));
    }
}
