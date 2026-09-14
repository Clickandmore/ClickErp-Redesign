<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ComplaintSubmissionMethodEnum;
use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\RecruitmentOffices;
use App\Models\RentalContract;
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
        $this->middleware('permission:' . PermKeys::COMPLAINT_EDIT . '|' . PermKeys::COMPLAINT_CONTROL . ',admin')->only(['linkRecord', 'schedule']);
        $this->middleware('permission:' . PermKeys::COMPLAINT_CREATE . '|' . PermKeys::COMPLAINT_EDIT . '|' . PermKeys::COMPLAINT_CONTROL . ',admin')->only(['searchContracts', 'searchOrders']);
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
            $query = objModel::query()->with([
                'user', 'complaintType', 'assignedTo', 'maidNationality',
                'contract.maid.recruitmentOffice', 'order.contracts.maid.recruitmentOffice',
                'relatedUser', 'recruitmentOffice',
                // مطلوبة لاستخراج اسم العاملة في عمود الجدول دون استعلام لكل صف
                'contract.maid.nationality', 'contract.maid.office', 'contract.info.manualMaidOffice',
                'contract.requiredMaidNationality', 'contract.travelHistory',
                'order.maid.nationality', 'order.maid.office',
                'order.contracts.maid.nationality', 'order.contracts.maid.office',
                'order.contracts.info.manualMaidOffice', 'order.contracts.requiredMaidNationality',
                'order.contracts.travelHistory',
            ]);
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
                ->addColumn('external_office', fn ($row) => $row->external_office?->title)
                ->addColumn('related_record', function ($row) {
                    return $this->resolveComplaintRelatedRecord($row);
                })
                ->addColumn('maid_party', function ($row) {
                    return $this->resolveComplaintMaidParty($row);
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
                                ->orWhere('against_name', 'LIKE', "%{$search}%")
                                ->orWhere('maid_passport', 'LIKE', "%{$search}%")
                                ->orWhereHas('contract.maid', function ($qq) use ($search) {
                                    $qq->where('name', 'LIKE', "%{$search}%");
                                })
                                ->orWhereHas('order.maid', function ($qq) use ($search) {
                                    $qq->where('name', 'LIKE', "%{$search}%");
                                })
                                ->orWhere('priority', 'LIKE', "%{$search}%")
                                ->orWhere('status', 'LIKE', "%{$search}%")
                                ->orWhere('related_to', 'LIKE', "%{$search}%")
                                ->orWhere('relation_reference', 'LIKE', "%{$search}%")
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
        $query = objModel::query()->with([
            'complaintType', 'assignedTo', 'maidNationality', 'recruitmentOffice',
            'contract.maid.nationality', 'contract.maid.office', 'contract.info.manualMaidOffice',
            'contract.requiredMaidNationality', 'contract.travelHistory',
            'order.maid.nationality', 'order.maid.office',
            'order.contracts.maid.nationality', 'order.contracts.maid.office',
            'order.contracts.info.manualMaidOffice', 'order.contracts.requiredMaidNationality',
            'order.contracts.travelHistory',
        ]);
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
            fputcsv($output, ['رقم الشكوى', 'مقدم الشكوى', 'المشتكى عليه', 'اسم العاملة', 'الوكيل الخارجي', 'الموعد المستهدف', 'الموضوع', 'النوع', 'الأولوية', 'الحالة', 'الموظف المختص', 'التاريخ']);

            foreach ($complaints as $complaint) {
                fputcsv($output, [
                    $complaint->id,
                    $complaint->complainant_name,
                    $complaint->against_name,
                    $this->resolveComplaintMaidParty($complaint)['name'] ?? null,
                    $complaint->external_office?->title,
                    $complaint->due_at?->format('Y-m-d H:i'),
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
        $prefill = null;
        if (request()->filled('contract_id')) {
            request()->validate(['contract_id' => 'required|integer']);
            $contract = Contract::findOrFail(request('contract_id'));
            $this->assertCanAccessBranchModel($contract, 'contracts');
            $prefill = new objModel(['contract_id' => $contract->id, 'relation_type' => 'contract']);
        }
        return view($this->folderPath . '.create', $this->complaintFormData($prefill) + [
            'isEdit' => false, 'prefillContractId' => $prefill?->contract_id,
        ]);
    }

    public function searchContracts(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:255', 'page' => 'nullable|integer|min:1']);
        $search = trim($data['q'] ?? '');
        $query = Contract::query()->with(['user', 'maid', 'info']);
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('id', $search)
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('maid', function ($maid) use ($search) {
                        $maid->where('name', 'like', "%{$search}%")
                            ->orWhere('passport_num', 'like', "%{$search}%");
                    });
            });
        }
        $contracts = $query->latest('id')->simplePaginate(25);

        return response()->json([
            'results' => $contracts->getCollection()->map(fn (Contract $contract) => [
                'id' => $contract->id, 'text' => $this->contractSelectorLabel($contract),
            ])->values(),
            'pagination' => ['more' => $contracts->hasMorePages()],
        ]);
    }

    public function searchOrders(Request $request)
    {
        $data = $request->validate(['q' => 'nullable|string|max:255', 'page' => 'nullable|integer|min:1']);
        $search = trim($data['q'] ?? '');
        $query = Order::query()->with(['user', 'maid']);
        if ($search !== '') {
            $query->where(function ($query) use ($search) {
                $query->where('order_code', 'like', "%{$search}%")
                    ->orWhereHas('user', function ($user) use ($search) {
                        $user->where('name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    })
                    ->orWhereHas('maid', function ($maid) use ($search) {
                        $maid->where('name', 'like', "%{$search}%")
                            ->orWhere('passport_num', 'like', "%{$search}%");
                    });
                if (ctype_digit($search)) {
                    $query->orWhere('id', $search);
                }
            });
        }
        $orders = $query->latest('id')->simplePaginate(25, ['*'], 'page', (int) ($data['page'] ?? 1));

        return response()->json([
            'results' => $orders->getCollection()->map(fn (Order $order) => [
                'id' => $order->id,
                'text' => implode(' — ', array_filter([
                    'طلب #' . ($order->order_code ?: $order->id),
                    $order->user?->name ?: 'بدون عميل',
                    $order->maid?->name,
                ])),
            ])->values(),
            'pagination' => ['more' => $orders->hasMorePages()],
        ]);
    }

    private function complaintFormData(?objModel $complaint = null): array
    {
        $users = User::orderBy('name')->get(['id', 'name', 'phone']);
        $nationalities = Nationality::with('translations')->get()->sortBy('title');
        $complaintTypes = ComplaintType::query()
            ->where(function ($query) use ($complaint) {
                $query->where('is_active', true);
                if ($complaint?->complaint_type_id) {
                    $query->orWhere('id', $complaint->complaint_type_id);
                }
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();
        $orders = collect();
        if ($complaint?->order_id && !$orders->contains('id', $complaint->order_id)) {
            $orders->prepend(Order::query()->with(['user', 'maid'])->findOrFail($complaint->order_id));
        }
        $oldOrderId = old('order_id');
        if ($oldOrderId && !$orders->contains('id', $oldOrderId)) {
            $oldOrder = Order::with(['user', 'maid'])->find($oldOrderId);
            if ($oldOrder) {
                $orders->prepend($oldOrder);
            }
        }
        $contracts = collect();
        if ($complaint?->contract_id && !$contracts->contains('id', $complaint->contract_id)) {
            $contracts->prepend(Contract::query()->with(['user', 'maid'])->findOrFail($complaint->contract_id));
        }
        $oldContractId = old('contract_id');
        if ($oldContractId && !$contracts->contains('id', $oldContractId)) {
            $oldContract = Contract::with(['user', 'maid', 'info'])->find($oldContractId);
            if ($oldContract) {
                $contracts->prepend($oldContract);
            }
        }
        $contractOptions = $contracts->map(fn (Contract $contract) => [
            'id' => $contract->id,
            'label' => $this->contractSelectorLabel($contract),
        ])->values();
        $recruitmentOffices = RecruitmentOffices::query()->with('translations')->orderBy('id')->get();
        $partyTypes = ComplaintType::partyTypes();

        return compact(
            'users', 'nationalities', 'complaintTypes', 'admins', 'orders', 'contracts',
            'contractOptions', 'recruitmentOffices', 'partyTypes'
        );
    }

    /**
     * نص خيار العقد في قائمة ربط الشكوى — التاريخ والحالة يميّزان عقود العميل الواحد.
     */
    private function contractSelectorLabel(Contract $contract): string
    {
        $contractDate = $contract->info?->contract_date ?: $contract->contract_date;
        $status = \App\Enums\ingaz\ContractStatusEnum::tryFrom((string) $contract->status);

        return implode(' — ', array_filter([
            'عقد #' . $contract->id,
            $contract->user?->name ?: 'بدون عميل',
            $contract->maid?->name,
            $contractDate ? \Carbon\Carbon::parse($contractDate)->format('Y-m-d') : null,
            $status?->lang(),
        ]));
    }

    public function store(Request $request, UserAction $userAction)
    {
        try {
            $request->validate([
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => $this->complainantPhoneRules(),
                'complainant_type' => 'required|in:client,worker,internal_office,external_office,employee,other',
                'subject' => 'required|string|max:255',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'details' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'user_id' => 'nullable|exists:users,id',
                'maid_passport' => 'nullable|string|max:50',
                'maid_nationality_id' => 'nullable|exists:nationalities,id',
                'assigned_to' => 'nullable|exists:admins,id',
                'after_save' => 'nullable|in:list,shelter',
                'relation_type' => 'required|in:none,order,contract,office,client,manual' . ($request->input('after_save') === 'shelter' ? '|in:contract' : ''),
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

            $data = $request->except('attachments', 'attachment_names', 'after_save');
            $data['status'] = 'pending';
            $data['message'] = $request->details; // للتوافق مع الحقل القديم
            // الشكاوى المُدخلة من لوحة التحكم تُحفظ دائماً بهذه القناة — تُفرض بعد except حتى لا تُزوَّر من الطلب
            $data['submission_method'] = ComplaintSubmissionMethodEnum::Dashboard->value;
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

            $redirectUrl = $request->input('after_save') === 'shelter'
                ? route('ShelterContract.create', ['contract_id' => $complaint->contract_id, 'shelter_complaint_id' => $complaint->id])
                : route($this->mainRoute . '.index');

            // إرجاع استجابة JSON للـ AJAX
            if ($request->ajax()) {
                return response()->json([
                    'code' => 201,
                    'message' => 'تم إضافة الشكوى بنجاح',
                    'data' => [
                        'url' => $redirectUrl
                    ]
                ]);
            }

            return redirect()->to($redirectUrl)
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

    private function complainantPhoneRules(): array
    {
        return ['required', 'regex:/^(05|5)[0-9]{8}$/'];
    }

    /**
     * Normalize both current complaint relations and imported legacy `related_to`
     * values into one payload used by the list and details pages.
     */
    private function resolveComplaintRelatedRecord(objModel $complaint): ?array
    {
        if ($complaint->relation_type === 'order' && $complaint->order) {
            return $this->relatedRecordPayload(
                'order',
                'طلب استقدام',
                $complaint->order->order_code ?: $complaint->order->id,
                route('orders.show', $complaint->order->id),
                'عرض الطلب'
            );
        }

        if ($complaint->relation_type === 'contract' && $complaint->contract) {
            $isShelterContract = $complaint->contract->status === 'shelter';

            return $this->relatedRecordPayload(
                $isShelterContract ? 'shelter_contract' : 'contract',
                $isShelterContract ? 'عقد إيواء' : 'عقد استقدام',
                $complaint->contract->id,
                $isShelterContract
                    ? route('ShelterContract.show', $complaint->contract->id)
                    : route('contracts.details', $complaint->contract->id),
                'عرض العقد'
            );
        }

        if ($complaint->relation_type === 'office' && $complaint->recruitmentOffice) {
            return $this->relatedRecordPayload(
                'office',
                'مكتب خارجي',
                $complaint->recruitmentOffice->title ?: $complaint->recruitmentOffice->id,
                route('recruitment-offices.show', $complaint->recruitmentOffice->id),
                'عرض المكتب'
            );
        }

        if ($complaint->relation_type === 'client' && $complaint->relatedUser) {
            return $this->relatedRecordPayload(
                'client',
                'عميل',
                $complaint->relatedUser->name ?: $complaint->relatedUser->id,
                route('clients.show', $complaint->relatedUser->id),
                'عرض العميل'
            );
        }

        if ($complaint->relation_type === 'manual') {
            $reference = trim((string) ($complaint->relation_reference ?: $complaint->related_to));

            return $reference !== '' && $reference !== '-'
                ? $this->relatedRecordPayload('manual', 'مرجع يدوي', $reference)
                : null;
        }

        $legacyRelation = trim((string) $complaint->related_to);
        if ($legacyRelation === '' || $legacyRelation === '-') {
            return null;
        }

        if (preg_match('/^(?:طلب|عقد)\s+تأجير\s*#?\s*([\p{N}-]+)$/u', $legacyRelation, $matches)) {
            return $this->legacyRentalContractRecord($matches[1]);
        }

        if (preg_match('/^طلب(?:\s+استقدام)?\s*#?\s*([\p{L}\p{N}-]+)$/u', $legacyRelation, $matches)) {
            return $this->legacyOrderRecord($matches[1]);
        }

        if (preg_match('/^عقد\s+إيواء\s*#?\s*(\d+)$/u', $legacyRelation, $matches)) {
            return $this->relatedRecordPayload(
                'shelter_contract',
                'عقد إيواء',
                $matches[1],
                route('ShelterContract.show', (int) $matches[1]),
                'عرض العقد'
            );
        }

        if (preg_match('/^عقد\s+استقدام\s*#?\s*(\d+)$/u', $legacyRelation, $matches)) {
            return $this->relatedRecordPayload(
                'contract',
                'عقد استقدام',
                $matches[1],
                route('contracts.details', (int) $matches[1]),
                'عرض العقد'
            );
        }

        return $this->relatedRecordPayload('reference', 'مرجع', $legacyRelation);
    }

    /** ذاكرة مؤقتة داخل الطلب الواحد لمراجع الاستيراد حتى لا تتكرر الاستعلامات لكل صف. */
    private array $legacyRecordCache = [];

    /**
     * مرجع الاستيراد يحمل رقم الطلب (order_code مثل ARB-2476) وليس معرّف الصف،
     * لذلك نطابق الكود أولا ثم المعرّف حتى لا يقود الرابط إلى طلب خاطئ أو صفحة 404.
     * الأولوية لكود ARB لأن الرقم المجرد يطابق أيضا طلبات قديمة تخص عملاء آخرين.
     */
    private function legacyOrderRecord(string $reference): array
    {
        $order = $this->legacyRecordCache['order:' . $reference] ??= (
            Order::query()->where('order_code', 'ARB-' . $reference)->first(['id', 'order_code'])
            ?: Order::query()->where('order_code', $reference)->first(['id', 'order_code'])
            ?: (ctype_digit($reference) ? Order::query()->whereKey((int) $reference)->first(['id', 'order_code']) : null)
        );

        return $this->relatedRecordPayload(
            'order',
            'طلب استقدام',
            $order?->order_code ?: $reference,
            $order ? route('orders.show', $order->id) : null,
            'عرض الطلب'
        );
    }

    /**
     * عقود التأجير المستوردة تُطابق برقم العقد (contract_number) ثم بالمعرّف.
     */
    private function legacyRentalContractRecord(string $reference): array
    {
        $rentalContract = $this->legacyRecordCache['rental:' . $reference] ??= (
            RentalContract::query()->where('contract_number', $reference)->first(['id', 'contract_number'])
            ?: (ctype_digit($reference) ? RentalContract::query()->whereKey((int) $reference)->first(['id', 'contract_number']) : null)
        );

        return $this->relatedRecordPayload(
            'rental_contract',
            'عقد تأجير',
            $rentalContract?->contract_number ?: $reference,
            $rentalContract ? route('RentContract.show', $rentalContract->id) : null,
            'عرض عقد التأجير'
        );
    }

    /**
     * أيقونة كل نوع ارتباط — الألوان نفسها معرفة في partials/related-record-styles.blade.php
     * عبر الصنف relation-badge--{type}.
     */
    private const RELATED_RECORD_ICONS = [
        'order' => 'fa-clipboard-list',
        'contract' => 'fa-file-contract',
        'shelter_contract' => 'fa-home',
        'rental_contract' => 'fa-handshake',
        'office' => 'fa-building',
        'client' => 'fa-user',
        'manual' => 'fa-pen',
        'reference' => 'fa-link',
    ];

    private function relatedRecordPayload(
        string $type,
        string $label,
        string|int $reference,
        ?string $url = null,
        ?string $actionLabel = null
    ): array {
        return [
            'type' => $type,
            'label' => $label,
            'reference' => (string) $reference,
            'url' => $url,
            'action_label' => $actionLabel,
            'icon' => self::RELATED_RECORD_ICONS[$type] ?? self::RELATED_RECORD_ICONS['reference'],
        ];
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

    /**
     * هل الشكوى موجهة ضد عاملة؟ الشكاوى المستوردة تحمل نصا عربيا في against_type
     * بدلا من المفتاح worker، ولذلك نفحص النص ونوع الشكوى أيضا.
     */
    private function complaintIsAgainstWorker(objModel $complaint): bool
    {
        $againstType = $complaint->against_type ?: $complaint->complaintType?->against_type;

        if ($againstType === 'worker') {
            return true;
        }

        if (is_string($againstType) && mb_strpos($againstType, 'عامل') !== false) {
            return true;
        }

        return (bool) preg_match('/عميل.*ضد.*عامل/u', (string) $complaint->complaintType?->name);
    }

    /**
     * اسم العاملة المعروض في جدول الشكاوى: من بيانات المشتكى عليه حين تكون الشكوى
     * ضد عاملة، وإلا من العقد أو الطلب المرتبط بالشكوى.
     */
    private function resolveComplaintMaidParty(objModel $complaint): ?array
    {
        $isAgainstWorker = $this->complaintIsAgainstWorker($complaint);
        $party = [];
        $contract = null;
        $maid = null;

        if ($complaint->relation_type === 'contract' && $complaint->contract) {
            $contract = $complaint->contract;
            $maid = $contract->maid;
            $party = $this->resolveContractAgainstParty($complaint->contract, 'worker');
        } elseif ($complaint->relation_type === 'order' && $complaint->order) {
            $contract = $complaint->order->contracts->first(function (Contract $contract) {
                return $contract->maid || filled($contract->info?->manual_maid_name);
            });
            $maid = $complaint->order->maid ?: $contract?->maid;
            $party = $this->resolveOrderAgainstParty($complaint->order, 'worker');
        }
        $name = $isAgainstWorker && filled($complaint->against_name)
            ? $complaint->against_name
            : ($party['name'] ?? null);

        if (blank($name)) {
            return null;
        }

        $nationality = $complaint->maidNationality?->title
            ?: $maid?->nationality?->title
            ?: $contract?->requiredMaidNationality?->title;
        $warranty = $this->resolveComplaintWarrantyDetails($contract);

        return [
            'name' => $name,
            'passport' => $complaint->maid_passport ?: ($party['maid_passport'] ?? null),
            'office' => $complaint->external_office?->title,
            'nationality' => $nationality,
            'days_since_arrival' => $warranty['days_since_arrival'],
            'warranty_status' => $warranty['status'],
            'warranty_remaining_days' => $warranty['remaining_days'],
        ];
    }

    private function resolveComplaintWarrantyDetails(?Contract $contract): array
    {
        if (!$contract) {
            return ['days_since_arrival' => null, 'status' => null, 'remaining_days' => null];
        }

        $startDate = $contract->warranty_start_date;
        if (!$startDate && $contract->travelHistory?->status === 'completed') {
            $startDate = $contract->travelHistory->resolveArrivalDateTime();
        }
        $startDate ??= $contract->info?->arrival_date;
        $startDate ??= $contract->maid?->arrival_date;

        $daysSinceArrival = $startDate
            ? max(0, (int) \Carbon\Carbon::parse($startDate)->startOfDay()->diffInDays(today(), false))
            : null;

        if (!$contract->warranty_end_date) {
            return [
                'days_since_arrival' => $daysSinceArrival,
                'status' => null,
                'remaining_days' => null,
            ];
        }

        $endDate = \Carbon\Carbon::parse($contract->warranty_end_date)->startOfDay();
        $isExpired = $endDate->copy()->endOfDay()->isPast();

        return [
            'days_since_arrival' => $daysSinceArrival,
            'status' => $isExpired ? 'expired' : 'active',
            'remaining_days' => $isExpired
                ? 0
                : max(0, (int) today()->diffInDays($endDate, false)),
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
            'user', 'complaintType', 'assignedTo', 'resolvedBy', 'maidNationality',
            // بيانات العقد المرتبط تُعرض داخل صفحة تفاصيل الشكوى
            'contract.user', 'contract.maid.nationality', 'contract.maid.office',
            'contract.marketer', 'contract.info.manualMaidOffice', 'contract.requiredMaidNationality',
            'contract.requiredMaidJobTitle',
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

        $relatedRecord = $this->resolveComplaintRelatedRecord($complaint);

        $historyService = app(\App\Services\Complaints\ComplaintHistory::class);
        $history = $historyService->forComplaint($complaint);
        $historySummary = $historyService->summary($complaint);
        $maidParty = $this->resolveComplaintMaidParty($complaint);
        $recruitmentOffices = RecruitmentOffices::with('translations')->get();
        $relatedClients = User::orderBy('name')->get(['id', 'name']);
        return view($this->folderPath . '.show', compact('complaint', 'admins', 'againstParty', 'relatedRecord', 'history', 'historySummary', 'maidParty', 'recruitmentOffices', 'relatedClients'));
    }

    public function edit($id)
    {
        $complaint = objModel::with('attachmentFiles')->findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        $data = $this->complaintFormData($complaint);
        $data['complaint'] = $complaint;
        $data['isEdit'] = true;

        return view($this->folderPath . '.create', $data);
    }

    public function update(Request $request, $id)
    {
        try {
            $complaint = objModel::findOrFail($id);
            $this->assertCanAccessBranchModel($complaint);

            $request->validate([
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => $this->complainantPhoneRules(),
                'complainant_type' => 'required|in:client,worker,internal_office,external_office,employee,other',
                'subject' => 'required|string|max:255',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'details' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'status' => 'required|in:pending,in_progress,resolved,closed,rejected',
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

            $data = $request->except('attachments', 'attachment_names', '_method', '_token');
            $data['message'] = $request->details; // للتوافق مع الحقل القديم
            $data['complainant_phone'] = ltrim((string) $data['complainant_phone'], '0');
            $complaintType = ComplaintType::findOrFail($data['complaint_type_id']);
            $data['complainant_type'] = $complaintType->complainant_type ?: $data['complainant_type'];
            $data['against_type'] = $complaintType->against_type ?: ($data['against_type'] ?? $complaint->against_type);
            $this->applyComplaintRelation($data);

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
        $query = objModel::with([
            'complaintType', 'assignedTo',
            // نفس علاقات عمود "متعلق بـ" في الجدول حتى تعرض البطاقات الارتباط أيضا
            'contract.maid.office', 'contract.maid.recruitmentOffice', 'contract.info.manualMaidOffice',
            'order.maid.office', 'order.contracts.maid.office', 'order.contracts.maid.recruitmentOffice',
            'order.contracts.info.manualMaidOffice', 'relatedUser', 'recruitmentOffice',
        ]);
        $this->applyRequestBranchFilter($query, $request, 'complaints');
        $this->applyComplaintFilters($query, $request);

        $complaints = $query->orderBy('created_at', 'desc')->limit(50)->get()
            ->map(function (objModel $complaint) {
                return $complaint->toArray() + [
                    'related_record' => $this->resolveComplaintRelatedRecord($complaint),
                    'external_office' => $complaint->external_office?->title,
                ];
            });

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
        $this->validateAssignment($request);
        $request->validate(['comment' => 'required|string|max:10000', 'status' => 'nullable|in:pending,in_progress,resolved,closed,rejected']);
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
            'added_by' => auth('admin')->id(),
            'attachments' => $request->attachments ?? null
        ]);

        $this->notifyComplaintStatusChanged($complaint, $previousStatus);
        $notificationResults = $this->notifyComplaintAssignmentChanged($complaint, $previousAssignedTo, $request->input('notification_channels', $request->boolean('notification_channels_present') ? [] : ['database']), $request->input('notes'));

        if ($previousStatus === $complaint->status && (int) $previousAssignedTo === (int) $complaint->assigned_to) {
            $this->notifyComplaintUpdated($complaint, 'تم إضافة تحديث على الشكوى');
        }

        return response()->json(['success' => true, 'message' => 'تم إضافة التحديث بنجاح', 'notification_results' => $notificationResults]);
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
        $this->validateAssignment($request, true);
        $complaint = objModel::findOrFail($request->complaint_id);
        $this->assertCanAccessBranchModel($complaint);
        $previousAssignedTo = $complaint->assigned_to;
        $complaint->assigned_to = $request->assigned_to;
        $complaint->save();
        $notificationResults = $this->notifyComplaintAssignmentChanged($complaint, $previousAssignedTo, $request->input('notification_channels', $request->boolean('notification_channels_present') ? [] : ['database']), $request->input('notes'));

        return response()->json(['success' => true, 'message' => 'تم تحويل التذكرة بنجاح', 'notification_results' => $notificationResults]);
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

    private function notifyComplaintAssignmentChanged(objModel $complaint, $previousAssignedTo, array $channels = ['database'], ?string $notes = null): array
    {
        if ((int) $previousAssignedTo === (int) $complaint->assigned_to) {
            return [];
        }
        return app(\App\Services\Complaints\ComplaintAssignmentNotifier::class)
            ->send($complaint, $channels, $notes);
    }

    private function validateAssignment(Request $request, bool $required = false): void
    {
        $request->validate([
            'assigned_to' => ($required ? 'required' : 'nullable') . '|integer|exists:admins,id',
            'notification_channels' => 'sometimes|array',
            'notification_channels.*' => 'in:database,sms,email',
            'notes' => 'nullable|string|max:2000',
        ]);
        if ($request->filled('assigned_to') && !Admin::activeForOperations()->whereKey($request->assigned_to)->exists()) {
            throw ValidationException::withMessages(['assigned_to' => 'اختر موظفاً نشطاً.']);
        }
    }

    public function schedule(Request $request, objModel $complaint)
    {
        $this->assertCanAccessBranchModel($complaint);
        $data = $request->validate([
            'due_at' => 'nullable|date|after:now',
            'schedule_notes' => 'nullable|string|max:2000',
        ]);
        $complaint->update($data);
        return back()->with('success', 'تم تحديث موعد معالجة الشكوى');
    }

    public function linkRecord(Request $request, objModel $complaint)
    {
        $this->assertCanAccessBranchModel($complaint);
        $data = $request->validate([
            'relation_type' => 'required|in:contract,order,office,client,manual',
            'contract_id' => 'nullable|required_if:relation_type,contract|integer',
            'order_id' => 'nullable|required_if:relation_type,order|integer',
            'recruitment_office_id' => 'nullable|required_if:relation_type,office|integer',
            'related_user_id' => 'nullable|required_if:relation_type,client|integer',
            'against_name' => 'nullable|required_if:relation_type,manual|string|max:255',
            'relation_reference' => 'nullable|string|max:255',
            'relation_notes' => 'nullable|string|max:2000',
        ]);
        $models = ['contract' => [Contract::class, 'contract_id'], 'order' => [Order::class, 'order_id'],
            'office' => [RecruitmentOffices::class, 'recruitment_office_id'], 'client' => [User::class, 'related_user_id']];
        if (isset($models[$data['relation_type']])) {
            [$model, $key] = $models[$data['relation_type']];
            $record = $model::findOrFail($data[$key]);
            $this->assertCanAccessBranchModel($record);
        }
        $data['against_type'] = $complaint->against_type ?: $complaint->complaintType?->against_type;
        $this->applyComplaintRelation($data);
        $complaint->update($data);
        return back()->with('success', 'تم ربط الشكوى بنجاح');
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
