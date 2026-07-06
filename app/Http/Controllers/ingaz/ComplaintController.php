<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\NotificationActionEnum;
use App\Http\Actions\ingaz\UserAction;
use App\Http\Controllers\admin\Controller;
use App\Http\Traits\SendSMSTrait;
use App\Http\Traits\StoreMessageTrait;
use App\Models\auth\Admin;
use App\Models\ingaz\Complaint as objModel;
use App\Models\ingaz\ComplaintComment;
use App\Models\ingaz\ComplaintType;
use App\Models\ingaz\Nationality;
use App\Models\ingaz\User;
use App\Services\AdminNotificationService;
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
        // $this->middleware('permission:الشكاوى-قراءة,admin', ['only' => ['index']]);
        // $this->middleware('permission:الشكاوى-انشاء,admin', ['only' => ['create', 'store']]);
        // $this->middleware('permission:الشكاوى-تعديل,admin', ['only' => ['edit', 'update']]);
        // $this->middleware('permission:الشكاوى-حذف,admin', ['only' => ['destroy']]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = objModel::query()->with(['user', 'complaintType', 'assignedTo', 'maidNationality', 'contract']);
            $this->applyRequestBranchFilter($query, $request, 'complaints');

            // تطبيق الفلاتر
            if ($request->ticket_number) {
                $ticketNumber = ltrim($request->ticket_number, '#0');
                $ticketNumber = $ticketNumber === '' ? 0 : $ticketNumber;
                $query->where('id', 'LIKE', '%' . $ticketNumber . '%');
            }

            if ($request->status) {
                $query->where('status', $request->status);
            }

            if ($request->priority) {
                $query->where('priority', $request->priority);
            }

            if ($request->type) {
                $query->where('complaint_type_id', $request->type);
            }

            if ($request->assigned_to) {
                $query->where('assigned_to', $request->assigned_to);
            }

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
                    return $row->file ? Storage::url($row->file) : null;
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

    public function create()
    {
        $users = \App\Models\ingaz\User::orderBy('name')->get();
        $nationalities = Nationality::with('translations')->get()->sortBy('title');
        $complaintTypes = ComplaintType::orderBy('name')->get();
        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();

        return view($this->folderPath . '.create', compact('users', 'nationalities', 'complaintTypes', 'admins'));
    }

    public function store(Request $request, UserAction $userAction)
    {
        try {
            $request->validate([
                'complainant_name' => 'required|string|max:255',
                'complainant_phone' => [
                    'required',
                    'regex:/^(05|5)[0-9]{8}$/',
                    function ($attribute, $value, $fail) {
                        $phoneWithout0 = ltrim($value, '0');
                        if (!User::whereIn('phone', [$phoneWithout0, $value])->exists()) {
                            $fail('رقم الجوال غير مسجل في النظام، يرجى التحقق من الرقم أو اختر عميلاً من القائمة');
                        }
                    },
                ],
                'subject' => 'required|string|max:255',
                'complaint_type_id' => 'required|exists:complaint_types,id',
                'details' => 'required|string',
                'priority' => 'required|in:low,medium,high,urgent',
                'user_id' => 'nullable|exists:users,id',
                'maid_passport' => 'nullable|string|max:50',
                'maid_nationality_id' => 'nullable|exists:nationalities,id',
                'assigned_to' => 'nullable|exists:admins,id',
                'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
            ]);

            $data = $request->except('attachments');
            $data['status'] = 'pending';
            $data['message'] = $request->details; // للتوافق مع الحقل القديم

            // معالجة المرفقات
            if ($request->hasFile('attachments')) {
                $attachmentPaths = [];
                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints/attachments', 'public');
                    $attachmentPaths[] = $path;
                }
                $data['attachments'] = $attachmentPaths;
            }
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
            if (!$user) {
                throw ValidationException::withMessages([
                    'complainant_phone' => ['رقم الجوال غير مسجل في النظام، يرجى التحقق من الرقم أو اختر عميلاً من القائمة'],
                ]);
            }

            $data['user_id'] = $user->id;
            $complaint = objModel::create($data);

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

    public function show($id)
    {
        $complaint = objModel::with(['user', 'complaintType', 'assignedTo', 'resolvedBy', 'maidNationality', 'contract'])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        $admins = Admin::query()->activeForOperations()->orderBy('name')->get();

        // إضافة شارات الحالة والأولوية
        $complaint->statusBadge = $this->getStatusBadge($complaint->status);
        $complaint->priorityBadge = $this->getPriorityBadge($complaint->priority);

        return view($this->folderPath . '.show', compact('complaint', 'admins'));
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
                'attachments.*' => 'nullable|file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx'
            ]);

            $data = $request->except('attachments', '_method');
            $data['message'] = $request->details; // للتوافق مع الحقل القديم

            // معالجة المرفقات الجديدة
            if ($request->hasFile('attachments')) {
                $attachmentPaths = $complaint->attachments ?? [];
                if (!is_array($attachmentPaths)) {
                    $attachmentPaths = [];
                }

                foreach ($request->file('attachments') as $file) {
                    $path = $file->store('complaints/attachments', 'public');
                    $attachmentPaths[] = $path;
                }
                $data['attachments'] = $attachmentPaths;
            }

            // تحديث حالة الحل إذا تم تغيير الحالة
            if ($request->status == 'resolved' && $complaint->status != 'resolved') {
                $data['resolved_at'] = now();
                $data['resolved_by'] = auth()->guard('admin')->id();
            }

            $complaint->update($data);

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

        // تطبيق الفلاتر
        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if ($request->type) {
            $query->where('complaint_type_id', $request->type);
        }
        if ($request->assigned_to) {
            $query->where('assigned_to', $request->assigned_to);
        }

        $complaints = $query->orderBy('created_at', 'desc')->limit(50)->get();

        return response()->json(['data' => $complaints]);
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

    public function destroy($id)
    {
        $complaint = objModel::findOrFail($id);
        $this->assertCanAccessBranchModel($complaint);

        // حذف المرفقات
        if ($complaint->attachments && is_array($complaint->attachments)) {
            foreach ($complaint->attachments as $attachment) {
                \Storage::disk('public')->delete($attachment);
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

        if (!$complaint_comment) {
            return jsonError(['error' => 'هذا التعليق غير موجود'], 404);
        }

        $complaint_comment->delete();

        return jsonSuccess(['success' => 'تم حذف التعليق بنجاح']);
    }
}
