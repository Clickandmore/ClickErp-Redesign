<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\Controller;
use App\Http\Requests\ingaz\makeTransferRequest;
use App\Models\Contract;
use App\Models\ingaz\User;
use App\Models\ingaz\Order;
use App\Models\SponsorshipTransfer;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class SponsorshipTransferController extends Controller
{
    public $folderPath = "admin.ingaz.SponsorshipTransfer";
    public $mainRoute = "SponsorshipTransfer";


    public function index(Request $request)
    {
        // إذا كان الطلب من DataTables
        if ($request->ajax()) {
            $query = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'approvedBy']);

            // تطبيق الفلاتر
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('contract_number')) {
                $query->whereHas('contract', function($q) use ($request) {
                    $q->where('id', 'like', '%' . $request->contract_number . '%');
                });
            }

            if ($request->filled('old_sponsor')) {
                $query->whereHas('oldSponsor', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->old_sponsor . '%')
                      ->orWhere('phone', 'like', '%' . $request->old_sponsor . '%');
                });
            }

            if ($request->filled('new_sponsor')) {
                $query->whereHas('newSponsor', function($q) use ($request) {
                    $q->where('name', 'like', '%' . $request->new_sponsor . '%')
                      ->orWhere('phone', 'like', '%' . $request->new_sponsor . '%');
                });
            }

            if ($request->filled('trial_days')) {
                $query->where('trial_days', $request->trial_days);
            }

            if ($request->filled('amount')) {
                $amountRange = explode('-', $request->amount);
                if (count($amountRange) == 2) {
                    $query->whereBetween('contract_value', [$amountRange[0], $amountRange[1]]);
                } elseif ($request->amount == '15000+') {
                    $query->where('contract_value', '>=', 15000);
                }
            }

            $transfers = $query->latest()->get();

            return DataTables::of($transfers)
                ->addIndexColumn()
                ->addColumn('contract_number', function($transfer) {
                    return $transfer->contract ? 'عقد رقم: ' . $transfer->contract->id : 'غير محدد';
                })
                ->addColumn('old_sponsor', function($transfer) {
                    if ($transfer->oldSponsor) {
                        return '<div class="user-info">
                                    <strong>' . $transfer->oldSponsor->name . '</strong><br>
                                    <small class="text-muted">هوية: ' . ($transfer->oldSponsor->national_num ?? 'غير محدد') . '</small><br>
                                    <small class="text-muted">جوال: ' . $transfer->oldSponsor->phone . '</small>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('new_sponsor', function($transfer) {
                    if ($transfer->newSponsor) {
                        return '<div class="user-info">
                                    <strong>' . $transfer->newSponsor->name . '</strong><br>
                                    <small class="text-muted">هوية: ' . ($transfer->newSponsor->national_num ?? 'غير محدد') . '</small><br>
                                    <small class="text-muted">جوال: ' . $transfer->newSponsor->phone . '</small>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('maid_info', function($transfer) {
                    if ($transfer->contract && $transfer->contract->maid) {
                        $maid = $transfer->contract->maid;
                        return '<div class="maid-info">
                                    <strong>' . $maid->name . '</strong><br>
                                    <small class="text-muted">جواز: ' . ($maid->passport_num ?? 'غير محدد') . '</small><br>
                                    <small class="text-muted">جنسية: ' . ($maid->nationality->title ?? 'غير محدد') . '</small>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('trial_days', function($transfer) {
                    return $transfer->trial_days . ' يوم';
                })
                ->addColumn('trial_start_date', function($transfer) {
                    return $transfer->trial_start_date ? $transfer->trial_start_date->format('Y-m-d') : 'غير محدد';
                })
                ->addColumn('trial_end_info', function($transfer) {
                    if ($transfer->trial_start_date && $transfer->trial_days) {
                        $endDate = $transfer->trial_start_date->addDays($transfer->trial_days);
                        $now = now();
                        $daysRemaining = $now->diffInDays($endDate, false);

                        $html = '<div class="trial-info">';
                        $html .= '<strong>تنتهي: ' . $endDate->format('Y-m-d') . '</strong><br>';

                        if ($daysRemaining > 0) {
                            $html .= '<small class="text-success">متبقي: ' . $daysRemaining . ' يوم</small>';
                        } elseif ($daysRemaining == 0) {
                            $html .= '<small class="text-warning">تنتهي اليوم</small>';
                        } else {
                            $html .= '<small class="text-danger">انتهت منذ: ' . abs($daysRemaining) . ' يوم</small>';
                        }

                        $html .= '</div>';
                        return $html;
                    }
                    return 'غير محدد';
                })
                ->addColumn('contract_value', function($transfer) {
                    return number_format($transfer->contract_value, 2) . ' ريال';
                })
                ->addColumn('paid_amount', function($transfer) {
                    return number_format($transfer->paid_amount, 2) . ' ريال';
                })
                ->addColumn('remaining_amount', function($transfer) {
                    return number_format($transfer->remaining_amount, 2) . ' ريال';
                })
                ->addColumn('status', function($transfer) {
                    $badgeClass = '';
                    switch ($transfer->status) {
                        case 'pending':
                            $badgeClass = 'bg-warning';
                            break;
                        case 'waiting_contract_send':
                            $badgeClass = 'bg-info';
                            break;
                        case 'contract_sent':
                            $badgeClass = 'bg-primary';
                            break;
                        case 'contract_signed':
                            $badgeClass = 'bg-success';
                            break;
                        case 'maid_delivered':
                            $badgeClass = 'bg-success';
                            break;
                        case 'trial_started':
                            $badgeClass = 'bg-primary';
                            break;
                        case 'approved':
                            $badgeClass = 'bg-info';
                            break;
                        case 'trial':
                            $badgeClass = 'bg-primary';
                            break;
                        case 'transferred':
                            $badgeClass = 'bg-success';
                            break;
                        case 'failed':
                            $badgeClass = 'bg-danger';
                            break;
                        case 'rejected':
                            $badgeClass = 'bg-danger';
                            break;
                        case 'completed':
                            $badgeClass = 'bg-success';
                            break;
                        default:
                            $badgeClass = 'bg-secondary';
                    }
                    return '<span class="badge ' . $badgeClass . '">' . $transfer->status_text . '</span>';
                })
                ->addColumn('created_at', function($transfer) {
                    return $transfer->created_at ? $transfer->created_at->format('Y-m-d H:i') : 'غير محدد';
                })
                ->addColumn('actions', function($transfer) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button" aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض الطلب
                    $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.show', $transfer->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';

                    // طباعة العقد (انتظار إرسال العقد)
                    if ($transfer->status == 'waiting_contract_send') {
                        $actions .= '<li><a class="dropdown-item" href="' . route('print.sponsorship-transfer-contract', $transfer->id) . '" target="_blank">
                                        <i class="fas fa-print"></i> طباعة العقد
                                    </a></li>';
                    }

                    // تأكيد إرسال العقد للعميل (انتظار إرسال العقد)
                    if ($transfer->status == 'waiting_contract_send') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="send-contract" data-id="' . $transfer->id . '" data-text="تأكيد إرسال العقد للعميل">
                                        <i class="fas fa-paper-plane"></i> تأكيد إرسال العقد
                                    </a></li>';
                    }

                    // تسجيل توقيع العقد (تم إرسال العقد)
                    if ($transfer->status == 'contract_sent') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="sign-contract" data-id="' . $transfer->id . '" data-text="تسجيل توقيع العقد">
                                        <i class="fas fa-signature"></i> تسجيل توقيع العقد
                                    </a></li>';
                    }

                    // تسليم العاملة (تم توقيع العقد)
                    if ($transfer->status == 'contract_signed') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="deliver-maid" data-id="' . $transfer->id . '" data-text="تسجيل تسليم العاملة">
                                        <i class="fas fa-handshake"></i> تسجيل تسليم العاملة
                                    </a></li>';
                    }

                    // بدء التجربة (تم تسليم العاملة)
                    if ($transfer->status == 'maid_delivered') {
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.trial-details', $transfer->id) . '">
                                        <i class="fas fa-play"></i> عرض تفاصيل التجربة
                                    </a></li>';
                    }

                    // تأكيد النقل (فقط إذا كان في فترة التجربة)
                    // if ($transfer->status == 'trial') {
                    //     $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="confirm-transfer" data-id="' . $transfer->id . '" data-text="تأكيد نقل الكفالة">
                    //                     <i class="fas fa-check-circle"></i> تأكيد النقل
                    //                 </a></li>';
                    // }
                    if ($transfer->status == 'trial_started') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="confirm-transfer" data-id="' . $transfer->id . '" data-text="تأكيد نقل الكفالة">
                                        <i class="fas fa-check-circle"></i> تأكيد النقل
                                    </a></li>';
                    }

                    // إلغاء النقل (فقط إذا كان في فترة التجربة)
                    // if ($transfer->status == 'trial') {
                    //     $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="cancel-transfer" data-id="' . $transfer->id . '" data-text="إلغاء النقل وإعادة العقد إلى الإيواء">
                    //                     <i class="fas fa-times-circle"></i> إلغاء النقل
                    //                 </a></li>';
                    // }
                    if ($transfer->status == 'trial_started') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="cancel-transfer" data-id="' . $transfer->id . '" data-text="إلغاء النقل وإعادة العقد إلى الإيواء">
                                        <i class="fas fa-times-circle"></i> إلغاء النقل
                                    </a></li>';
                    }

                    // الرفض (فقط إذا كان في الانتظار)
                    if ($transfer->status == 'pending') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="reject" data-id="' . $transfer->id . '" data-text="رفض الطلب">
                                        <i class="fas fa-times"></i> رفض
                                    </a></li>';
                    }

                    // حذف (فقط إذا كان في الانتظار)
                    if ($transfer->status == 'pending') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="destroy" data-id="' . $transfer->id . '" data-text="حذف الطلب">
                                        <i class="fas fa-trash"></i> حذف
                                    </a></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['old_sponsor', 'new_sponsor', 'maid_info', 'trial_end_info', 'status', 'actions'])
                ->make(true);
        }

        // حساب الإحصائيات
        $pendingCount = SponsorshipTransfer::where('status', 'pending')->count();
        $trialCount = SponsorshipTransfer::where('status', 'trial')->count();
        $transferredCount = SponsorshipTransfer::where('status', 'transferred')->count();
        $failedCount = SponsorshipTransfer::where('status', 'failed')->count();
        $rejectedCount = SponsorshipTransfer::where('status', 'rejected')->count();
        $approvedCount = SponsorshipTransfer::where('status', 'approved')->count();
        $completedCount = SponsorshipTransfer::where('status', 'completed')->count();

        return view($this->folderPath . '.index', compact(
            'pendingCount',
            'trialCount',
            'transferredCount',
            'failedCount',
            'rejectedCount',
            'approvedCount',
            'completedCount'
        ));
    }

    /**
     * عرض طلبات نقل الكفالة من الموقع الخارجي
     */
    public function webRequests(Request $request)
    {
        if ($request->ajax()) {
            $query = Order::with(['user', 'maid.nationality', 'details.admin'])
                ->where('job_type_id', 3) // طلبات نقل الكفالة
                ->orderBy('created_at', 'desc');

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('order_info', function ($row) {
                    return '<div class="order-info">
                                <strong>طلب رقم: ' . $row->order_code . '</strong><br>
                                <small class="text-muted">تاريخ الطلب: ' . $row->created_at->format('Y-m-d H:i') . '</small>
                            </div>';
                })
                ->addColumn('client_info', function ($row) {
                    return '<div class="client-info">
                                <strong>' . ($row->user ? $row->user->name : 'غير محدد') . '</strong><br>
                                <small class="text-muted">' . ($row->user ? $row->user->phone : 'غير محدد') . '</small>
                            </div>';
                })
                ->addColumn('maid_info', function ($row) {
                    if (!$row->maid) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    return '<div class="maid-info">
                                <strong>' . $row->maid->name . '</strong><br>
                                <small class="text-muted">الجنسية: ' . ($row->maid->nationality ? $row->maid->nationality->sub_title : 'غير محدد') . '</small><br>
                                <small class="text-muted">رقم الجواز: ' . ($row->maid->passport_num ?? 'غير محدد') . '</small>
                            </div>';
                })
                ->addColumn('admin_info', function ($row) {
                    if (!$row->details || !$row->details->admin) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    return '<div class="admin-info">
                                <strong>' . $row->details->admin->name . '</strong><br>
                                <small class="text-muted">ممثل خدمة العملاء</small>
                            </div>';
                })
                ->addColumn('status', function ($row) {
                    $statusBadges = [
                        'new' => 'badge-warning',
                        'paid' => 'badge-info',
                        'completed' => 'badge-success',
                        'started' => 'badge-primary',
                        'done' => 'badge-success',
                        'refused' => 'badge-danger',
                        'canceled' => 'badge-secondary'
                    ];

                    $badgeClass = $statusBadges[$row->status] ?? 'badge-secondary';
                    return '<span class="badge ' . $badgeClass . '">' . $row->status . '</span>';
                })
                ->addColumn('actions', function ($row) {
                    $actions = '<div class="dropdown actions-dropdown">
                                    <button class="btn btn-sm btn-primary dropdown-toggle actions-btn" type="button"  aria-expanded="false" title="الإجراءات المتاحة">
                                        <i class="fas fa-cogs me-1"></i> الإجراءات
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end shadow-lg">';

                    // عرض تفاصيل الطلب
                    $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.webRequest.show', $row->id) . '">
                                    <i class="fas fa-eye"></i> عرض التفاصيل
                                </a></li>';

                    // إنشاء طلب نقل كفالة
                    if ($row->status == 'new' || $row->status == 'paid') {
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.create', ['order_id' => $row->id]) . '">
                                        <i class="fas fa-exchange-alt"></i> إنشاء طلب نقل كفالة
                                    </a></li>';
                    }

                    // إلغاء الطلب وإرجاع العاملة متاحة
                    if ($row->status == 'new' || $row->status == 'paid') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="cancel-order" data-id="' . $row->id . '" data-text="إلغاء الطلب وإرجاع العاملة متاحة مرة أخرى">
                                        <i class="fas fa-times"></i> إلغاء الطلب
                                    </a></li>';
                    }

                    // تحديث حالة الطلب
                    if ($row->status == 'new') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="update-status" data-id="' . $row->id . '" data-status="started" data-text="تحديث حالة الطلب إلى معالج">
                                        <i="fas fa-play"></i> بدء المعالجة
                                        </a></li>';
                    }

                    $actions .= '</ul></div>';

                    return $actions;
                })
                ->rawColumns(['order_info', 'client_info', 'maid_info', 'admin_info', 'status', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.web-requests');
    }

    /**
     * عرض تفاصيل طلب نقل الكفالة من الموقع الخارجي
     */
    public function showWebRequest($id)
    {
        $order = Order::with(['user', 'maid.nationality', 'details.admin'])
            ->where('job_type_id', 3)
            ->findOrFail($id);

                return view($this->folderPath . '.show-web-request', compact('order'));
    }

    public function create()
    {
        // التحقق من وجود order_id في الطلب
        $orderId = request('order_id');
        $selectedOrder = null;
        $selectedContractId = request('contract_id');

        // إذا تم تمرير order_id، نجلب تفاصيل الطلب
        if ($orderId) {
            $selectedOrder = Order::with(['maid', 'user'])->find($orderId);

            // إذا كان الطلب موجوداً وله عاملة، نبحث عن العقد المناسب
            if ($selectedOrder && $selectedOrder->maid) {
                // البحث عن العقد الذي يحتوي على هذه العاملة في الإيواء
                $matchingContract = Contract::with(['maid', 'user', 'info'])
                    ->where('maid_id', $selectedOrder->maid_id)
                    ->where('status', \App\Enums\ingaz\ContractStatusEnum::Shelter->value)
                    ->whereHas('info', function($query) {
                        $query->where('wants_to_work', 'yes');
                    })
                    ->first();

                if ($matchingContract) {
                    $selectedContractId = $matchingContract->id;
                } else {
                    // إضافة رسالة تحذيرية إذا لم يتم العثور على العقد المناسب
                    session()->flash('warning', 'لم يتم العثور على عقد مناسب للعاملة المطلوبة في الإيواء أو العاملة غير راغبة في العمل. يرجى التحقق من حالة العاملة.');
                }
            }
        }

        $selectedNewSponsorId = request('new_sponsor_id');
        $selectedNewSponsor = null;
        if ($selectedNewSponsorId) {
            $selectedNewSponsor = User::find($selectedNewSponsorId);
        }

        // جلب العقود المتاحة للنقل (العقود داخل الإيواء مع work_willingness = 'yes')
        $contracts = Contract::with(['maid', 'user', 'info'])
            ->where('status', \App\Enums\ingaz\ContractStatusEnum::Shelter->value)
            ->whereHas('info', function($query) {
                $query->where('wants_to_work', 'yes');
            })
            ->latest()
            ->get();

        return view($this->folderPath . '.create', compact('contracts', 'selectedContractId', 'selectedOrder', 'selectedNewSponsorId', 'selectedNewSponsor'));
    }


    public function store(Request $request)
    {
        try {
            // Validation يدوي
            $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'new_sponsor_id' => 'required|exists:users,id',
                'trial_days' => 'required|integer|min:1|max:90',
                'trial_start_date' => 'required|date',
                'contract_value' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ], [
                'contract_id.required' => 'يجب اختيار العقد',
                'contract_id.exists' => 'العقد المختار غير موجود',
                'new_sponsor_id.required' => 'يجب اختيار الكفيل الجديد',
                'new_sponsor_id.exists' => 'الكفيل الجديد غير موجود',
                'trial_days.required' => 'يجب إدخال عدد أيام العمل التجريبي',
                'trial_days.integer' => 'عدد أيام العمل التجريبي يجب أن يكون رقماً صحيحاً',
                'trial_days.min' => 'عدد أيام العمل التجريبي يجب أن يكون 1 يوم على الأقل',
                'trial_days.max' => 'عدد أيام العمل التجريبي يجب أن يكون 90 يوم كحد أقصى',
                'trial_start_date.required' => 'يجب إدخال تاريخ بداية العمل التجريبي',
                'trial_start_date.date' => 'تاريخ بداية العمل التجريبي غير صحيح',
                'contract_value.required' => 'يجب إدخال قيمة العقد',
                'contract_value.numeric' => 'قيمة العقد يجب أن تكون رقماً',
                'contract_value.min' => 'قيمة العقد يجب أن تكون أكبر من صفر',
                'paid_amount.required' => 'يجب إدخال المبلغ المدفوع',
                'paid_amount.numeric' => 'المبلغ المدفوع يجب أن يكون رقماً',
                'paid_amount.min' => 'المبلغ المدفوع يجب أن يكون أكبر من أو يساوي صفر',
                'notes.max' => 'الملاحظات يجب أن تكون أقل من 1000 حرف',
            ]);

            $contract = Contract::find($request->contract_id);

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            // التحقق من أن العقد متاح للنقل
            if (!$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العقد غير متاح للنقل'
                ], 400);
            }

            // التحقق من وجود الكفيل الجديد
            $newSponsor = User::find($request->new_sponsor_id);
            if (!$newSponsor) {
                return response()->json([
                    'success' => false,
                    'message' => 'الكفيل الجديد غير موجود'
                ], 404);
            }

            // حفظ بيانات طلب نقل الكفالة
            $transferData = [
                'contract_id' => $contract->id,
                'old_sponsor_id' => $contract->user_id,
                'new_sponsor_id' => $request->new_sponsor_id,
                'trial_days' => $request->trial_days,
                'trial_start_date' => $request->trial_start_date,
                'contract_value' => $request->contract_value,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->contract_value - $request->paid_amount,
                'notes' => $request->notes,
                'status' => 'waiting_contract_send', // الحالة الجديدة: انتظار إرسال العقد
            ];

            // إنشاء طلب نقل كفالة جديد
            SponsorshipTransfer::create($transferData);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ طلب نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            \Log::error('Error in SponsorshipTransfer store: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حفظ طلب نقل الكفالة'
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $transfer = SponsorshipTransfer::with([
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor',
            'approvedBy',
            'transferredBy',
            'failedBy'
        ])->findOrFail($id);

        return view($this->folderPath . '.show', compact('transfer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        $transfer = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor'])->findOrFail($id);

        if ($transfer->status !== 'pending') {
            return redirect()->back()->with('error', 'لا يمكن تحرير هذا الطلب');
        }

        // جلب العقود المتاحة للنقل
        $contracts = Contract::with(['maid', 'user', 'info'])
            ->whereHas('info', function($query) {
                $query->where('wants_to_work', 'yes');
            })
            ->latest()
            ->get();

        return view($this->folderPath . '.edit', compact('transfer', 'contracts'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تحديث هذا الطلب'
                ], 400);
            }

            // Validation
            $request->validate([
                'new_sponsor_id' => 'required|exists:users,id',
                'trial_days' => 'required|integer|min:1|max:90',
                'trial_start_date' => 'required|date',
                'contract_value' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0',
                'notes' => 'nullable|string|max:1000',
            ]);

            $transfer->update([
                'new_sponsor_id' => $request->new_sponsor_id,
                'trial_days' => $request->trial_days,
                'trial_start_date' => $request->trial_start_date,
                'contract_value' => $request->contract_value,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->contract_value - $request->paid_amount,
                'notes' => $request->notes,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث طلب نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث طلب نقل الكفالة'
            ], 500);
        }
    }

    /**
     * Approve transfer request and start trial period
     */
    public function approve($id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن الموافقة على هذا الطلب'
                ], 400);
            }

            $transfer->update([
                'status' => 'trial',
                'approved_by' => auth()->guard('admin')->id(),
                'approved_at' => now(),
                'trial_started_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تمت الموافقة على الطلب وبدأت فترة التجربة'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في الموافقة على الطلب'
            ], 500);
        }
    }

    /**
     * Reject transfer request
     */
    public function reject(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن رفض هذا الطلب'
                ], 400);
            }

            $transfer->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason ?? 'لم يتم تحديد سبب الرفض',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم رفض طلب نقل الكفالة'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في رفض الطلب'
            ], 500);
        }
    }

    /**
     * Complete transfer request
     */
    public function complete($id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            if ($transfer->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إكمال هذا الطلب'
                ], 400);
            }

            $transfer->update([
                'status' => 'completed'
            ]);

            // يمكن إضافة منطق إضافي هنا لتحديث العقد أو العاملة

            return response()->json([
                'success' => true,
                'message' => 'تم إكمال طلب نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إكمال الطلب'
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذا الطلب'
                ], 400);
            }

            $transfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف طلب نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف الطلب'
            ], 500);
        }
    }

    /**
     * البحث في العملاء للاستخدام في Select2
     */
    public function searchClients(Request $request)
    {
        $search = $request->input('search');

        $clients = User::where('name', 'LIKE', "%{$search}%")
            ->orWhere('id_number', 'LIKE', "%{$search}%")
            ->orWhere('phone', 'LIKE', "%{$search}%")
            ->select('id', 'name', 'id_number', 'phone', 'city')
            ->limit(10)
            ->get();

        $results = $clients->map(function ($client) {
            return [
                'id' => $client->id,
                'text' => $client->name . ' - ' . ($client->id_number ?: 'بدون هوية') . ' - ' . ($client->phone ?: 'بدون جوال')
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Confirm transfer - move to transferred status
     */
    public function confirmTransfer($id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            // if ($transfer->status !== 'trial') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يمكن تأكيد النقل لهذا الطلب'
            //     ], 400);
            // }

            // if ($transfer->status !== 'trial_started') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يمكن تأكيد النقل لهذا الطلب'
            //     ], 400);
            // }

            // لانه يستخدم كل حالة في مكان مختلف لذلك هتعامل مع القيمتين
            if ($transfer->status !== 'trial_started' && $transfer->status !== 'trial') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تأكيد النقل لهذا الطلب'
                ], 400);
            }

            // تحديث العقد بنقل الكفالة للكفيل الجديد
            $contract = Contract::find($transfer->contract_id);
            if ($contract) {
                $contract->update([
                    'user_id' => $transfer->new_sponsor_id
                ]);
            }

            $transfer->update([
                'status' => 'transferred',
                'transferred_by' => auth()->guard('admin')->id(),
                'transferred_at' => now(),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تأكيد النقل'
            ], 500);
        }
    }

    /**
     * Cancel transfer - move to failed status and return to shelter
     */
    public function cancelTransfer(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            // if ($transfer->status !== 'trial') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يمكن إلغاء النقل لهذا الطلب'
            //     ], 400);
            // }

            // if ($transfer->status !== 'trial_started') {
            //     return response()->json([
            //         'success' => false,
            //         'message' => 'لا يمكن إلغاء النقل لهذا الطلب'
            //     ], 400);
            // }
            

            // لانه يستخدم كل حالة في مكان مختلف لذلك هتعامل مع القيمتين
            if ($transfer->status !== 'trial_started' && $transfer->status !== 'trial') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء النقل لهذا الطلب'
                ], 400);
            }

            // إعادة العقد إلى الإيواء وجعله متاح للنقل
            $contract = Contract::find($transfer->contract_id);
            if ($contract && $contract->info) {
                $contract->info->update([
                    'wants_to_work' => 'yes' // متاح للنقل مرة أخرى
                ]);
            }

            $transfer->update([
                'status' => 'failed',
                'failed_by' => auth()->guard('admin')->id(),
                'failed_at' => now(),
                'failure_reason' => $request->failure_reason ?? 'لم يتم تحديد سبب الإلغاء',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء النقل وإعادة العقد إلى الإيواء'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إلغاء النقل'
            ], 500);
        }
    }

    /**
     * إلغاء طلب نقل الكفالة وإرجاع العاملة متاحة
     */
    public function cancelOrder(Request $request, $id)
    {
        try {
            $order = Order::findOrFail($id);

            if (!in_array($order->status, ['new', 'paid'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء الطلب في هذه الحالة'
                ], 400);
            }

            // إرجاع العاملة متاحة مرة أخرى
            if ($order->maid) {
                $order->maid->update([
                    'status' => 'available',
                    'available' => 1,
                    'available_at' => now()->addDays(30)->toDateString()
                ]);
            }

            // تحديث حالة الطلب إلى مرفوض
            $order->update([
                'status' => 'refused',
                'notes' => 'تم إلغاء الطلب من قبل الإدارة'
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء الطلب وإرجاع العاملة متاحة مرة أخرى'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إلغاء الطلب'
            ], 500);
        }
    }

    /**
     * Show trial period transfers
     */
    public function trial()
    {
        // $transfers = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'approvedBy'])
        //     ->where('status', 'trial')
        //     ->latest()
        //     ->paginate(25);

        $transfers = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'approvedBy'])
            ->whereIn('status', ['trial', 'trial_started'])
            ->latest()
            ->paginate(25);

        return view($this->folderPath . '.trial', compact('transfers'));
    }

    /**
     * Show completed transfers
     */
    public function transferred()
    {
        $transfers = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'transferredBy'])
            ->where('status', 'transferred')
            ->latest()
            ->paginate(25);

        return view($this->folderPath . '.transferred', compact('transfers'));
    }

    /**
     * Show failed transfers
     */
    public function failed()
    {
        $transfers = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'failedBy'])
            ->where('status', 'failed')
            ->latest()
            ->paginate(25);

        return view($this->folderPath . '.failed', compact('transfers'));
    }

    /**
     * إرسال العقد للعميل
     */
    public function sendContract(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            // التحقق من الحالة
            if (!$transfer->canSendContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرسال العقد في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'contract_sent',
                'contract_sent_at' => now(),
                'contract_sent_by' => auth('admin')->id()
            ]);

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد إرسال العقد للعميل بنجاح'
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Error in sendContract: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إرسال العقد'
            ], 500);
        }
    }

    /**
     * تسجيل توقيع العقد (تلقائي)
     */
    public function signContract(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            // التحقق من الحالة
            if (!$transfer->canSignContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسجيل توقيع العقد في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'contract_signed',
                'contract_signed_at' => now(),
                'contract_signed_by' => auth('admin')->id()
            ]);


            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل توقيع العقد بنجاح'
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Error in signContract: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تسجيل توقيع العقد'
            ], 500);
        }
    }

    /**
     * تسجيل تسليم العاملة (تلقائي)
     */
    public function deliverMaid(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);

            // التحقق من الحالة
            if (!$transfer->canDeliverMaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسجيل تسليم العاملة في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'maid_delivered',
                'maid_delivered_at' => now(),
                'maid_delivered_by' => auth('admin')->id()
            ]);


            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تسجيل تسليم العاملة بنجاح'
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Error in deliverMaid: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تسجيل تسليم العاملة'
            ], 500);
        }
    }

    /**
     * بدء فترة التجربة
     */
    public function startTrial(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::with(['transferServiceContract', 'contract'])->findOrFail($id);

            // إضافة logging للتشخيص
            \Log::info('Start Trial - Transfer ID: ' . $id . ', Current Status: ' . $transfer->status);

            // التحقق من الحالة
            if (!$transfer->canStartTrial()) {
                \Log::warning('Cannot start trial - Current status: ' . $transfer->status . ', Required: maid_delivered');
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن بدء فترة التجربة في الحالة الحالية. الحالة الحالية: ' . $transfer->getStatusTextAttribute() . '. يجب أن تكون الحالة: تم تسليم العاملة'
                ], 400);
            }

            \DB::beginTransaction();

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'trial_started',
                'trial_started_at' => now()
            ]);

            // تحديث حالة العقد الأصلي إلى "في التجربة"
            if ($transfer->contract) {
                $transfer->contract->update([
                    'status' => \App\Enums\ingaz\ContractStatusEnum::Trial->value
                ]);
            }

            \DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم بدء فترة التجربة بنجاح'
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('Error in startTrial: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في بدء فترة التجربة'
            ], 500);
        }
    }

    /**
     * طباعة عقد نقل الكفالة
     */
    public function printContract($id)
    {
        $transfer = SponsorshipTransfer::with([
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor'
        ])->findOrFail($id);

        // جلب بنود العقد
        $contractTerms = \App\Models\TransferContractTerm::active()
            ->ordered()
            ->get()
            ->groupBy('page_number');

        // إعدادات العقد (يمكن تخزينها في جدول منفصل لاحقاً)
        $contractSettings = [
            'company_name' => 'شركة إنجاز للاستقدام',
            'license_number' => '1234567890',
            'address' => 'الرياض، المملكة العربية السعودية',
            'phone' => '+966 11 123 4567',
            'email' => 'info@ingaz.com'
        ];

        return view('admin.print.sponsorship-transfer-contract-enhanced', compact('transfer', 'contractTerms', 'contractSettings'));
    }

    /**
     * عرض تفاصيل فترة التجربة للتأكيد
     */
    public function trialDetails($id)
    {
        $transfer = SponsorshipTransfer::with([
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor'
        ])->findOrFail($id);

        // التحقق من الحالة
        if ($transfer->status !== 'maid_delivered') {
            return redirect()->route('SponsorshipTransfer.index')
                ->with('error', 'لا يمكن عرض تفاصيل التجربة في الحالة الحالية');
        }

        return view('admin.ingaz.SponsorshipTransfer.trial-details', compact('transfer'));
    }

    /**
     * حفظ التوقيع على عقد نقل الكفالة
     */
    public function saveSignature(Request $request, $id)
    {
        try {
            // التحقق من وجود طلب النقل
            $transfer = SponsorshipTransfer::findOrFail($id);

            // التحقق من الحالة - يجب أن يكون العقد مُرسل
            if ($transfer->status !== 'contract_sent') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن التوقيع على العقد في الحالة الحالية'
                ], 400);
            }

            // التحقق من البيانات المرسلة
            $request->validate([
                'signature' => 'required|string'
            ]);

            // تحويل base64 إلى ملف
            $signatureData = $request->signature;

            // إزالة header من base64
            $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);

            // فك تشفير base64
            $signatureImage = base64_decode($signatureData);

            if (!$signatureImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات التوقيع غير صحيحة'
                ], 400);
            }

            \DB::beginTransaction();

            // إنشاء اسم فريد للملف
            $fileName = 'transfer_signature_' . $id . '_' . time() . '.png';

            // حفظ الملف في storage
            $filePath = 'signatures/' . $fileName;
            \Storage::disk('public')->put($filePath, $signatureImage);

            // تحديث طلب النقل بالتوقيع والحالة الجديدة
            $transfer->update([
                'signature' => $fileName,
                'status' => 'contract_signed',
                'contract_signed_at' => now(),
                'contract_signed_by' => auth('admin')->id()
            ]);

            \DB::commit();

            // إعادة تحميل النقل للتأكد من التحديث
            $transfer->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التوقيع بنجاح وتحديث حالة العقد',
                'signature_path' => $filePath,
                'signature_file' => $fileName,
                'new_status' => $transfer->status
            ]);

        } catch (\Exception $e) {
            \DB::rollback();
            \Log::error('خطأ في حفظ التوقيع: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ التوقيع: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * حفظ توقيع الشركة على عقد نقل الكفالة
     */
    public function saveCompanySignature(Request $request, $id)
    {
        try {
            // التحقق من وجود طلب النقل
            $transfer = SponsorshipTransfer::findOrFail($id);

            // التحقق من البيانات المرسلة
            $request->validate([
                'signature' => 'required|string'
            ]);

            // تحويل base64 إلى ملف
            $signatureData = $request->signature;

            // إزالة header من base64
            $signatureData = str_replace('data:image/png;base64,', '', $signatureData);
            $signatureData = str_replace(' ', '+', $signatureData);

            // فك تشفير base64
            $signatureImage = base64_decode($signatureData);

            if (!$signatureImage) {
                return response()->json([
                    'success' => false,
                    'message' => 'بيانات التوقيع غير صحيحة'
                ], 400);
            }

            // إنشاء اسم فريد للملف
            $fileName = 'company_signature_transfer_' . $id . '_' . time() . '.png';

            // حفظ الملف في storage
            $filePath = 'signatures/' . $fileName;
            \Storage::disk('public')->put($filePath, $signatureImage);

            // تحديث طلب النقل بتوقيع الشركة
            $transfer->update([
                'company_signature' => $fileName
            ]);

            // إعادة تحميل النقل للتأكد من التحديث
            $transfer->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ توقيع الشركة بنجاح',
                'signature_path' => $filePath,
                'signature_file' => $fileName
            ]);

        } catch (\Exception $e) {
            \Log::error('خطأ في حفظ توقيع الشركة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ توقيع الشركة: ' . $e->getMessage()
            ], 500);
        }
    }
}
