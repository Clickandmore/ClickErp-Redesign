<?php

namespace App\Http\Controllers\ingaz;

use App\Enums\ingaz\ContractStatusEnum;
use App\Http\Controllers\admin\Controller;
use App\Http\Requests\ingaz\makeTransferRequest;
use App\Models\auth\Admin;
use App\Models\Contract;
use App\Models\ingaz\Order;
use App\Models\ingaz\User;
use App\Models\SponsorshipTransfer;
use App\PermKeys;
use App\Services\ingaz\SponsorshipTransferCompletionService;
use App\Services\ingaz\SponsorshipTransferFailureService;
use App\Services\ingaz\SponsorshipTransferTrialStartService;
use App\Services\ingaz\SponsorshipTransferPaymentService;
use App\Services\ManagedFileStorage;
use App\Services\AdminNotificationService;
use App\Support\SystemModuleManager;
use App\Support\InvoiceAuthorization;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Yajra\DataTables\Facades\DataTables;

class SponsorshipTransferController extends Controller
{
    public $folderPath = "admin.ingaz.SponsorshipTransfer";
    public $mainRoute = "SponsorshipTransfer";


    public function index(Request $request)
    {
        // إذا كان الطلب من DataTables
        if ($request->ajax()) {
            $query = SponsorshipTransfer::with([
                'contract.maid.nationality',
                'oldSponsor',
                'newSponsor',
                'approvedBy',
                'invoice',
                'receiptBond',
                'refundBond',
            ]);
            $this->applyRequestBranchFilter($query, $request, 'sponsorship_transfers');

            // تطبيق الفلاتر
            if ($request->filled('status')) {
                if ($request->status === 'trial_period') {
                    $query->whereIn('status', ['trial', 'trial_started']);
                } else {
                    $query->where('status', $request->status);
                }
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
                                    <small class="text-muted">' . e($transfer->oldSponsor->documentNumberLabel()) . ': ' . e($transfer->oldSponsor->documentNumber() ?? 'غير محدد') . '</small><br>
                                    <small class="text-muted">جوال: ' . $transfer->oldSponsor->phone . '</small>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('new_sponsor', function($transfer) {
                    if ($transfer->newSponsor) {
                        return '<div class="user-info">
                                    <strong>' . $transfer->newSponsor->name . '</strong><br>
                                    <small class="text-muted">' . e($transfer->newSponsor->documentNumberLabel()) . ': ' . e($transfer->newSponsor->documentNumber() ?? 'غير محدد') . '</small><br>
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
                                    <small class="text-muted">جنسية: ' . ($maid->nationality->sub_title ?? $maid->nationality->title ?? 'غير محدد') . '</small>
                                </div>';
                    }
                    return 'غير محدد';
                })
                ->addColumn('trial_days', function($transfer) {
                    return $transfer->trial_days . ' يوم';
                })
                ->addColumn('trial_start_date', function($transfer) {
                    return $transfer->effective_trial_start_date
                        ? $transfer->effective_trial_start_date->format('Y-m-d')
                        : 'غير محدد';
                })
                ->addColumn('trial_end_info', function($transfer) {
                    if ($transfer->trial_end_date) {
                        $endDate = $transfer->trial_end_date;
                        $daysRemaining = $transfer->trial_days_remaining;

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

                    $actions .= '<li><hr class="dropdown-divider"></li>';
                    if ($transfer->invoice && InvoiceAuthorization::canView()) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('invoices.show', $transfer->invoice->id) . '">
                                        <i class="fas fa-file-invoice text-success"></i> عرض الفاتورة
                                    </a></li>';
                        if (InvoiceAuthorization::canPrint()) {
                            $actions .= '<li><a class="dropdown-item" href="' . route('invoice.download', ['id' => $transfer->invoice->id, 'autoprint' => 1]) . '" target="_blank">
                                            <i class="fas fa-print text-success"></i> تحميل / طباعة الفاتورة
                                        </a></li>';
                        }
                    } elseif (!$transfer->invoice && InvoiceAuthorization::canCreate()) {
                        $actions .= '<li><a class="dropdown-item fw-bold text-success" href="' . route('SponsorshipTransfer.invoice.create', $transfer->id) . '">
                                        <i class="fas fa-file-invoice-dollar"></i> إصدار الفاتورة
                                    </a></li>';
                    }

                    if ($transfer->payment_receipt_path) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.attachment.download', [
                            'id' => $transfer->id,
                            'type' => 'payment-receipt',
                        ]) . '">
                                        <i class="fas fa-paperclip text-primary"></i> تحميل إيصال الدفع
                                    </a></li>';
                    }

                    if ($transfer->receiptBond) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('finance.bonds-print.show', [
                            'bonds_print' => $transfer->receiptBond->id,
                            'type' => 'qabd',
                        ]) . '" target="_blank">
                                        <i class="fas fa-file-arrow-down text-primary"></i> تحميل / طباعة سند القبض
                                    </a></li>';
                    }

                    if ($transfer->refundBond) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('finance.bonds-print.show', [
                            'bonds_print' => $transfer->refundBond->id,
                            'type' => 'sarf',
                        ]) . '" target="_blank">
                                        <i class="fas fa-file-arrow-up text-danger"></i> تحميل / طباعة سند الصرف
                                    </a></li>';
                    }

                    foreach (array_values((array) $transfer->attachments) as $attachmentIndex => $attachment) {
                        if (empty($attachment['path'])) {
                            continue;
                        }

                        $attachmentName = Str::limit((string) ($attachment['name'] ?? ('مرفق ' . ($attachmentIndex + 1))), 35);
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.attachment.download', [
                            'id' => $transfer->id,
                            'type' => 'attachment',
                            'index' => $attachmentIndex,
                        ]) . '">
                                        <i class="fas fa-download text-secondary"></i> تحميل: ' . e($attachmentName) . '
                                    </a></li>';
                    }

                    if ($transfer->failure_client_iban_attachment_path) {
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.attachment.download', [
                            'id' => $transfer->id,
                            'type' => 'iban',
                        ]) . '">
                                        <i class="fas fa-download text-secondary"></i> تحميل مرفق الآيبان
                                    </a></li>';
                    }

                    // طباعة العقد (انتظار إرسال العقد)
                    if ($transfer->status == 'waiting_contract_send') {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item" href="' . route('print.sponsorship-transfer-contract', $transfer->id) . '" target="_blank">
                                        <i class="fas fa-print"></i> طباعة العقد
                                    </a></li>';
                    }

                    // تسجيل استلام العاملة مع تفعيل التجربة مباشرة
                    if (in_array($transfer->status, ['waiting_contract_send', 'contract_sent', 'contract_signed'])) {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="deliver-maid" data-id="' . $transfer->id . '" data-text="تسجيل استلام العاملة وتفعيل التجربة مباشرة">
                                        <i class="fas fa-handshake"></i> تسجيل استلام العاملة
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
                        $actions .= '<li><a class="dropdown-item cancel-transfer-action" href="#"
                                        data-id="' . $transfer->id . '"
                                        data-contract-value="' . (float) $transfer->contract_value . '"
                                        data-paid-amount="' . (float) $transfer->paid_amount . '"
                                        data-remaining-amount="' . (float) $transfer->remaining_amount . '">
                                        <i class="fas fa-times-circle"></i> إلغاء النقل
                                    </a></li>';
                    }

                    // الرفض (فقط إذا كان في الانتظار)
                    if ($transfer->status == 'pending') {
                        $actions .= '<li><a class="dropdown-item confirm-action" href="#" data-action="reject" data-id="' . $transfer->id . '" data-text="رفض الطلب">
                                        <i class="fas fa-times"></i> رفض
                                    </a></li>';
                    }

                    if ($transfer->canEditDetails() && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT)) {
                        $actions .= '<li><hr class="dropdown-divider"></li>';
                        $actions .= '<li><a class="dropdown-item" href="' . route('SponsorshipTransfer.edit', $transfer->id) . '">
                                        <i class="fas fa-edit"></i> تعديل
                                    </a></li>';
                    }

                    if ($transfer->canDeleteDetails() && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_DELETE)) {
                        if (!($transfer->canEditDetails() && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT))) {
                            $actions .= '<li><hr class="dropdown-divider"></li>';
                        }

                        $actions .= '<li><a class="dropdown-item text-danger confirm-action" href="#" data-action="destroy" data-id="' . $transfer->id . '" data-text="حذف الطلب من القائمة بدون حذفه نهائياً">
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
        $statsQuery = $this->applyRequestBranchFilter(SponsorshipTransfer::query(), $request, 'sponsorship_transfers');
        $totalCount = (clone $statsQuery)->count();
        $pendingCount = (clone $statsQuery)->where('status', 'pending')->count();
        $trialCount = (clone $statsQuery)->whereIn('status', ['trial', 'trial_started'])->count();
        $transferredCount = (clone $statsQuery)->where('status', 'transferred')->count();
        $failedCount = (clone $statsQuery)->where('status', 'failed')->count();
        $rejectedCount = (clone $statsQuery)->where('status', 'rejected')->count();
        $approvedCount = (clone $statsQuery)->where('status', 'approved')->count();
        $completedCount = (clone $statsQuery)->where('status', 'completed')->count();
        $receiptAccounts = SponsorshipTransferPaymentService::receiptAccounts();

        return view($this->folderPath . '.index', compact(
            'totalCount',
            'pendingCount',
            'trialCount',
            'transferredCount',
            'failedCount',
            'rejectedCount',
            'approvedCount',
            'completedCount',
            'receiptAccounts'
        ));
    }

    /**
     * عرض طلبات نقل الكفالة من الموقع الخارجي
     */
    public function webRequests(Request $request)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW), 403);

        $baseQuery = Order::query()->where('job_type_id', 3);
        $this->applyRequestBranchFilter($baseQuery, $request, 'orders');
        $statistics = [
            'new' => (clone $baseQuery)->where('status', 'new')->count(),
            'paid' => (clone $baseQuery)->where('status', 'paid')->count(),
            'completed' => (clone $baseQuery)->whereIn('status', ['completed', 'done'])->count(),
            'total' => (clone $baseQuery)->count(),
        ];

        if ($request->boolean('statistics')) {
            return response()->json([
                'statistics' => $statistics,
            ]);
        }

        if ($request->ajax()) {
            $query = Order::with(['user', 'maid.nationality', 'details.admin'])
                ->where('job_type_id', 3) // طلبات نقل الكفالة
                ->orderBy('created_at', 'desc');
            $this->applyRequestBranchFilter($query, $request, 'orders');

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
                    $responsibleAdmin = $row->resolveResponsibleAdmin();

                    if (!$responsibleAdmin) {
                        return '<span class="text-muted">غير محدد</span>';
                    }
                    return '<div class="admin-info">
                                <strong>' . $responsibleAdmin->name . '</strong><br>
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
                    // أزرار ظاهرة بجانب بعضها بدل قائمة منسدلة لم تكن تُفتح أصلاً،
                    // فلا تعتمد على أي تهيئة JavaScript في هذه الشاشة.
                    $actions = '<div class="order-actions-inline">';

                    // عرض تفاصيل الطلب
                    if (@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW)) {
                        $actions .= '<a class="btn btn-sm btn-outline-primary order-action" href="' . route('SponsorshipTransfer.webRequest.show', $row->id) . '" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i><span>عرض</span>
                                    </a>';
                    }

                    // إنشاء طلب نقل خدمات
                    if (($row->status == 'new' || $row->status == 'paid') && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_CREATE)) {
                        $actions .= '<a class="btn btn-sm btn-outline-success order-action" href="' . route('SponsorshipTransfer.create', ['order_id' => $row->id]) . '" title="إنشاء طلب نقل خدمات">
                                        <i class="fas fa-exchange-alt"></i><span>إنشاء نقل</span>
                                    </a>';
                    }

                    // تحديث حالة الطلب
                    if ($row->status == 'new' && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT)) {
                        $actions .= '<a class="btn btn-sm btn-outline-info order-action confirm-action" href="#" data-action="update-status" data-id="' . $row->id . '" data-status="started" data-text="تحديث حالة الطلب إلى معالج" title="بدء المعالجة">
                                        <i class="fas fa-play"></i><span>بدء المعالجة</span>
                                    </a>';
                    }

                    // إلغاء الطلب وإرجاع العاملة متاحة
                    if (($row->status == 'new' || $row->status == 'paid') && @AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT)) {
                        $actions .= '<a class="btn btn-sm btn-outline-danger order-action confirm-action" href="#" data-action="cancel-order" data-id="' . $row->id . '" data-text="إلغاء الطلب وإرجاع العاملة متاحة مرة أخرى" title="إلغاء الطلب">
                                        <i class="fas fa-times"></i><span>إلغاء</span>
                                    </a>';
                    }

                    $actions .= '</div>';

                    return $actions;
                })
                ->rawColumns(['order_info', 'client_info', 'maid_info', 'admin_info', 'status', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.web-requests', compact('statistics'));
    }

    /**
     * عرض تفاصيل طلب نقل الكفالة من الموقع الخارجي
     */
    public function showWebRequest($id)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_WEB_REQUESTS_VIEW), 403);

        $order = Order::with(['user', 'maid.nationality', 'details.admin'])
            ->where('job_type_id', 3)
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($order);

                return view($this->folderPath . '.show-web-request', compact('order'));
    }

    public function create()
    {
        $workflowSource = request('workflow_source') === 'rental' ? 'rental' : 'standard';
        // التحقق من وجود order_id في الطلب
        $orderId = request('order_id');
        $selectedOrder = null;
        $selectedContractId = request('contract_id');

        // إذا تم تمرير order_id، نجلب تفاصيل الطلب
        if ($orderId) {
            $selectedOrder = Order::with(['maid', 'user'])->find($orderId);
            if ($selectedOrder) {
                $this->assertCanAccessBranchModel($selectedOrder);
            }

            // إذا كان الطلب موجوداً وله عاملة، نبحث عن العقد المناسب
            if ($selectedOrder && $selectedOrder->maid) {
                // البحث عن العقد الذي يحتوي على هذه العاملة في الإيواء
                $matchingContractQuery = Contract::insideShelter()
                    ->with(['maid', 'user', 'info'])
                    ->where('maid_id', $selectedOrder->maid_id)
                    ->whereHas('info', function($query) {
                        $query->where('wants_to_work', 'yes');
                    })
                    ;
                $this->applyRequestBranchFilter($matchingContractQuery, request(), 'contracts');
                $matchingContract = $matchingContractQuery->first();

                if ($matchingContract) {
                    $selectedContractId = $matchingContract->id;
                } else {
                    // إضافة رسالة تحذيرية إذا لم يتم العثور على العقد المناسب
                    session()->flash('warning', 'لم يتم العثور على عقد مناسب للعاملة المطلوبة في الإيواء أو العاملة غير راغبة في العمل. يرجى التحقق من حالة العاملة.');
                }
            }
        }

        $selectedNewSponsorId = request('new_sponsor_id') ?? request('user_id');
        if (!$selectedNewSponsorId && $selectedOrder?->user_id) {
            $selectedNewSponsorId = $selectedOrder->user_id;
        }
        $selectedNewSponsor = null;
        if ($selectedNewSponsorId) {
            $selectedNewSponsor = User::find($selectedNewSponsorId);
        }

        // جلب العقود المتاحة للنقل (العقود داخل الإيواء مع work_willingness = 'yes')
        $contractsQuery = Contract::insideShelter()
            ->with(['maid.jobTypes', 'user', 'info'])
            ->whereHas('info', function($query) {
                $query->where('wants_to_work', 'yes');
            })
            ;
        $this->applyRequestBranchFilter($contractsQuery, request(), 'contracts');
        $contracts = $contractsQuery->latest()->get();

        if ($workflowSource === 'rental') {
            $contracts = $contracts
                ->filter(fn (Contract $contract) => $this->contractIsRentalWorker($contract))
                ->values();
        }

        $receiptAccounts = SponsorshipTransferPaymentService::receiptAccounts();

        return view($this->folderPath . '.create', compact('contracts', 'selectedContractId', 'selectedOrder', 'selectedNewSponsorId', 'selectedNewSponsor', 'receiptAccounts', 'workflowSource'));
    }


    public function store(Request $request, SponsorshipTransferPaymentService $paymentService)
    {
        try {
            $validated = $request->validate([
                'workflow_source' => 'nullable|in:standard,rental',
                'contract_id' => 'required|exists:contracts,id',
                'new_sponsor_mode' => 'required|in:existing,new',
                'new_sponsor_id' => 'required_if:new_sponsor_mode,existing|nullable|exists:users,id',
                'new_sponsor_name' => 'required_if:new_sponsor_mode,new|nullable|string|max:255',
                'new_sponsor_phone' => 'required_if:new_sponsor_mode,new|nullable|string|max:20',
                'new_sponsor_national_num' => 'nullable|string|max:50',
                'trial_days' => 'required|integer|min:1|max:90',
                'trial_start_date' => 'required|date',
                'contract_value' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0|lte:contract_value',
                'payment_type' => 'required|in:cash,bank_transfer,mada,tabby,tamara,pay_later,other',
                'payment_status' => 'required|in:paid,partial,pending,later,bank_pending',
                'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
                'receipt_account_id' => 'nullable|required_if:payment_status,paid,partial|integer|exists:fn_accounts,id',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:8192',
                'notes' => 'nullable|string|max:1000',
            ], [
                'contract_id.required' => 'يجب اختيار العقد',
                'contract_id.exists' => 'العقد المختار غير موجود',
                'new_sponsor_mode.required' => 'يجب تحديد طريقة إضافة العميل الجديد',
                'new_sponsor_mode.in' => 'طريقة إضافة العميل الجديد غير صحيحة',
                'new_sponsor_id.required_if' => 'يجب اختيار الكفيل الجديد',
                'new_sponsor_id.exists' => 'الكفيل الجديد غير موجود',
                'new_sponsor_name.required_if' => 'يجب إدخال اسم العميل الجديد',
                'new_sponsor_phone.required_if' => 'يجب إدخال رقم جوال العميل الجديد',
                'new_sponsor_phone.max' => 'رقم الجوال يجب أن يكون أقل من 20 خانة',
                'new_sponsor_national_num.max' => 'رقم الهوية يجب أن يكون أقل من 50 خانة',
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
                'paid_amount.lte' => 'المبلغ المدفوع لا يمكن أن يكون أكبر من قيمة العقد',
                'payment_type.required' => 'يجب اختيار طريقة الدفع',
                'payment_type.in' => 'طريقة الدفع غير صحيحة',
                'payment_status.required' => 'يجب اختيار حالة الدفع',
                'payment_status.in' => 'حالة الدفع غير صحيحة',
                'payment_receipt.file' => 'صيغة إيصال الدفع غير صحيحة',
                'payment_receipt.mimes' => 'صيغة إيصال الدفع يجب أن تكون pdf أو jpg أو jpeg أو png',
                'payment_receipt.max' => 'حجم إيصال الدفع يجب ألا يتجاوز 4 ميجابايت',
                'receipt_account_id.required_if' => 'يجب اختيار البنك أو حساب السداد لإصدار سند القبض',
                'receipt_account_id.exists' => 'حساب السداد المحدد غير موجود',
                'attachments.*.file' => 'صيغة أحد المرفقات غير صحيحة',
                'attachments.*.mimes' => 'صيغة المرفقات يجب أن تكون pdf أو jpg أو jpeg أو png أو doc أو docx',
                'attachments.*.max' => 'حجم كل مرفق يجب ألا يتجاوز 8 ميجابايت',
                'notes.max' => 'الملاحظات يجب أن تكون أقل من 1000 حرف',
            ]);

            $contract = Contract::with('info')->find($validated['contract_id']);
            if ($contract) {
                $this->assertCanAccessBranchModel($contract);
            }

            if (!$contract) {
                return response()->json([
                    'success' => false,
                    'message' => 'العقد غير موجود'
                ], 404);
            }

            $workflowSource = ($validated['workflow_source'] ?? 'standard') === 'rental'
                ? 'rental'
                : 'standard';
            if ($workflowSource === 'rental' && !$this->contractIsRentalWorker($contract)) {
                throw ValidationException::withMessages([
                    'contract_id' => 'مسار نقل الكفالة غير المالي مخصص لعاملات الإيجار فقط.',
                ]);
            }

            if ($workflowSource === 'rental') {
                $validated['paid_amount'] = 0;
                $validated['payment_type'] = 'pay_later';
                $validated['payment_status'] = 'later';
                $validated['receipt_account_id'] = null;
            } elseif (in_array($validated['payment_status'], ['paid', 'partial'], true) && !$request->hasFile('payment_receipt')) {
                throw ValidationException::withMessages([
                    'payment_receipt' => 'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع تم الدفع أو مدفوع جزئياً',
                ]);
            }

            if (!$contract->user_id) {
                throw ValidationException::withMessages([
                    'contract_id' => 'لا يمكن إنشاء طلب نقل خدمات لهذا العقد لأنه لا يحتوي على كفيل حالي. يرجى ربط العقد بالعميل الحالي أولا.',
                ]);
            }

            // التحقق من أن العقد متاح للنقل
            if (!$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العقد غير متاح للنقل'
                ], 400);
            }

            if ($contract->hasEscapeReport() || $contract->info->is_outside_shelter) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن نقل كفالة العاملة لوجود بلاغ هروب عليها'
                ], 422);
            }

            DB::beginTransaction();

            $newSponsor = $validated['new_sponsor_mode'] === 'existing'
                ? User::find($validated['new_sponsor_id'])
                : $this->findOrCreateNewSponsor($validated);
            if ($newSponsor) {
                $this->assertCanAccessBranchModel($newSponsor);
            }

            if (!$newSponsor) {
                throw ValidationException::withMessages([
                    'new_sponsor_id' => 'الكفيل الجديد غير موجود',
                ]);
            }

            if ((int) $newSponsor->id === (int) $contract->user_id) {
                throw ValidationException::withMessages([
                    'new_sponsor_id' => 'لا يمكن اختيار نفس العميل الحالي ككفيل جديد',
                ]);
            }

            $paymentReceiptPath = $workflowSource !== 'rental' && $request->hasFile('payment_receipt')
                ? app(ManagedFileStorage::class)->uploadFile(
                    $request->file('payment_receipt'),
                    'sponsorship-transfers/receipts'
                )
                : null;

            $attachments = [];
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = [
                    'path' => app(ManagedFileStorage::class)->uploadFile(
                        $file,
                        'sponsorship-transfers/attachments'
                    ),
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            // حفظ بيانات طلب نقل الكفالة
            $transferData = [
                'workflow_source' => $workflowSource,
                'contract_id' => $contract->id,
                'old_sponsor_id' => $contract->user_id,
                'new_sponsor_id' => $newSponsor->id,
                'trial_days' => $validated['trial_days'],
                'trial_start_date' => $validated['trial_start_date'],
                'contract_value' => $validated['contract_value'],
                'paid_amount' => $validated['paid_amount'],
                'remaining_amount' => $validated['contract_value'] - $validated['paid_amount'],
                'payment_type' => $validated['payment_type'],
                'receipt_account_id' => $validated['receipt_account_id'] ?? null,
                'payment_status' => $validated['payment_status'],
                'payment_receipt_path' => $paymentReceiptPath,
                'attachments' => $attachments ?: null,
                'payment_confirmed_at' => (float) $validated['paid_amount'] > 0 ? now() : null,
                'payment_confirmed_by' => (float) $validated['paid_amount'] > 0 ? auth('admin')->id() : null,
                'notes' => $validated['notes'] ?? null,
                'status' => 'waiting_contract_send', // الحالة الجديدة: انتظار إرسال العقد
            ];

            // إنشاء طلب نقل خدمات جديد
            $transfer = SponsorshipTransfer::create($transferData);
            $financeResult = null;
            if (!$transfer->isRentalWorkflow() && (float) $transfer->paid_amount > 0) {
                $financeResult = $paymentService->createReceipt(
                    $transfer,
                    (int) $validated['receipt_account_id'],
                    now()
                );
            }

            DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferCreated($transfer);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ طلب نقل الكفالة بنجاح'
                    . (!empty($financeResult['bond']) ? ' وإنشاء سند قبض رقم ' . ($financeResult['bond']->code ?: $financeResult['bond']->id) : ''),
                'transfer_id' => $transfer->id,
                'show_url' => route('SponsorshipTransfer.show', $transfer->id),
                'invoice_create_url' => InvoiceAuthorization::canCreate()
                    ? route('SponsorshipTransfer.invoice.create', $transfer->id)
                    : null,
            ]);

        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'البيانات المدخلة غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }
            \Log::error('Error in SponsorshipTransfer store: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->exceptionMessage('حدث خطأ في حفظ طلب نقل الكفالة', $e),
                'debug' => $this->exceptionDebug($e),
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
            'failedBy',
            'receiptAccount',
            'receiptBond.constraint',
            'invoice.invoiceConstraint',
            'refundAccount',
            'refundBond.constraint'
        ])->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);

        return view($this->folderPath . '.show', compact('transfer'));
    }

    public function downloadAttachment(int $id, string $type, ?int $index = null)
    {
        $transfer = SponsorshipTransfer::query()->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);

        [$path, $downloadName] = match ($type) {
            'payment-receipt' => [
                $transfer->payment_receipt_path,
                'payment-receipt-transfer-' . $transfer->id,
            ],
            'iban' => [
                $transfer->failure_client_iban_attachment_path,
                'iban-transfer-' . $transfer->id,
            ],
            'attachment' => $this->resolveTransferAttachmentDownload($transfer, $index),
            default => abort(404),
        };

        $storage = app(ManagedFileStorage::class);
        abort_if(!$path || !$storage->exists($path), 404);

        $extension = pathinfo($path, PATHINFO_EXTENSION);
        $downloadName = basename((string) $downloadName);
        if ($extension && !str_ends_with(strtolower($downloadName), '.' . strtolower($extension))) {
            $downloadName .= '.' . $extension;
        }

        return $storage->downloadResponse($path, $downloadName, $storage->mimeType($path));
    }

    /**
     * تحميل نموذج استلام العاملة (PDF) معبأً ببيانات طلب نقل الخدمات.
     */
    public function downloadMaidReceipt(int $id, \App\Services\ingaz\SponsorshipTransferMaidReceiptDocument $document)
    {
        $transfer = SponsorshipTransfer::query()->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);

        [$contents, $downloadName] = $document->generate($transfer);

        return response($contents, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="maid-receipt-transfer-' . $transfer->id . '.pdf"; filename*=UTF-8\'\'' . rawurlencode($downloadName),
        ]);
    }

    private function resolveTransferAttachmentDownload(SponsorshipTransfer $transfer, ?int $index): array
    {
        $attachments = array_values((array) $transfer->attachments);
        abort_if($index === null || !isset($attachments[$index]) || empty($attachments[$index]['path']), 404);

        $attachment = $attachments[$index];

        return [
            $attachment['path'],
            $attachment['name'] ?? ('transfer-' . $transfer->id . '-attachment-' . ($index + 1)),
        ];
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT), 403);

        $transfer = SponsorshipTransfer::with(['contract.maid.nationality', 'oldSponsor', 'newSponsor'])->findOrFail($id);
        $this->assertCanAccessBranchModel($transfer);

        if (!$transfer->canEditDetails()) {
            return redirect()->back()->with('error', 'لا يمكن تحرير هذا الطلب');
        }

        // جلب العقود المتاحة للنقل
        $contractsQuery = Contract::with(['maid', 'user', 'info'])
            ->whereHas('info', function($query) {
                $query->where('wants_to_work', 'yes');
            })
            ;
        $this->applyRequestBranchFilter($contractsQuery, request(), 'contracts');
        $contracts = $contractsQuery->latest()->get();

        $isEditMode = true;
        $workflowSource = $transfer->isRentalWorkflow() ? 'rental' : 'standard';
        $selectedContractId = $transfer->contract_id;
        $selectedNewSponsorId = $transfer->new_sponsor_id;
        $selectedNewSponsor = $transfer->newSponsor;
        $selectedOrder = null;
        $receiptAccounts = SponsorshipTransferPaymentService::receiptAccounts();

        return view($this->folderPath . '.create', compact(
            'transfer',
            'contracts',
            'isEditMode',
            'selectedContractId',
            'selectedOrder',
            'selectedNewSponsorId',
            'selectedNewSponsor',
            'receiptAccounts',
            'workflowSource'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, SponsorshipTransferPaymentService $paymentService, $id)
    {
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT), 403);

        try {
            $transfer = SponsorshipTransfer::findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if (!$transfer->canEditDetails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تحديث هذا الطلب'
                ], 400);
            }

            $validated = $request->validate([
                'contract_id' => 'required|exists:contracts,id',
                'new_sponsor_mode' => 'required|in:existing,new',
                'new_sponsor_id' => 'required_if:new_sponsor_mode,existing|nullable|exists:users,id',
                'new_sponsor_name' => 'required_if:new_sponsor_mode,new|nullable|string|max:255',
                'new_sponsor_phone' => 'required_if:new_sponsor_mode,new|nullable|string|max:20',
                'new_sponsor_national_num' => 'nullable|string|max:50',
                'trial_days' => 'required|integer|min:1|max:90',
                'trial_start_date' => 'required|date',
                'contract_value' => 'required|numeric|min:0',
                'paid_amount' => 'required|numeric|min:0|lte:contract_value',
                'payment_type' => 'required|in:cash,bank_transfer,mada,tabby,tamara,pay_later,other',
                'payment_status' => 'required|in:paid,partial,pending,later,bank_pending',
                'payment_receipt' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
                'receipt_account_id' => 'nullable|required_if:payment_status,paid,partial|integer|exists:fn_accounts,id',
                'attachments' => 'nullable|array',
                'attachments.*' => 'file|mimes:jpg,jpeg,png,pdf,doc,docx|max:8192',
                'notes' => 'nullable|string|max:1000',
            ]);

            if ($transfer->isRentalWorkflow()) {
                $validated['paid_amount'] = 0;
                $validated['payment_type'] = 'pay_later';
                $validated['payment_status'] = 'later';
                $validated['receipt_account_id'] = null;
            }

            if (
                !$transfer->isRentalWorkflow()
                && in_array($validated['payment_status'], ['paid', 'partial'], true)
                && !$request->hasFile('payment_receipt')
                && !$transfer->payment_receipt_path
            ) {
                throw ValidationException::withMessages([
                    'payment_receipt' => 'يجب إرفاق إيصال الدفع عند تحديد حالة الدفع تم الدفع أو مدفوع جزئياً',
                ]);
            }

            $contract = Contract::with('info')->find($validated['contract_id']);
            if ($contract) {
                $this->assertCanAccessBranchModel($contract);
            }

            if (!$contract || !$contract->info || $contract->info->wants_to_work !== 'yes') {
                return response()->json([
                    'success' => false,
                    'message' => 'هذا العقد غير متاح للنقل'
                ], 400);
            }

            if (!$contract->user_id) {
                throw ValidationException::withMessages([
                    'contract_id' => 'لا يمكن تحديث طلب نقل الكفالة بهذا العقد لأنه لا يحتوي على كفيل حالي. يرجى ربط العقد بالعميل الحالي أولا.',
                ]);
            }

            DB::beginTransaction();

            if ($transfer->receipt_bond_id && (
                round((float) $validated['paid_amount'], 2) !== round((float) $transfer->paid_amount, 2)
                || (string) $validated['payment_type'] !== (string) $transfer->payment_type
                || (int) ($validated['receipt_account_id'] ?? 0) !== (int) $transfer->receipt_account_id
            )) {
                throw ValidationException::withMessages([
                    'paid_amount' => 'لا يمكن تعديل المبلغ أو طريقة/حساب الدفع بعد إصدار سند القبض. استخدم سند قبض أو صرف جديد للتسوية.',
                ]);
            }

            $newSponsor = $validated['new_sponsor_mode'] === 'existing'
                ? User::find($validated['new_sponsor_id'])
                : $this->findOrCreateNewSponsor($validated);
            if ($newSponsor) {
                $this->assertCanAccessBranchModel($newSponsor);
            }

            if (!$newSponsor) {
                throw ValidationException::withMessages([
                    'new_sponsor_id' => 'الكفيل الجديد غير موجود',
                ]);
            }

            if ((int) $newSponsor->id === (int) $contract->user_id) {
                throw ValidationException::withMessages([
                    'new_sponsor_id' => 'لا يمكن اختيار نفس العميل الحالي ككفيل جديد',
                ]);
            }

            $paymentReceiptPath = $transfer->payment_receipt_path;
            if (!$transfer->isRentalWorkflow() && $request->hasFile('payment_receipt')) {
                $paymentReceiptPath = app(ManagedFileStorage::class)->uploadFile(
                    $request->file('payment_receipt'),
                    'sponsorship-transfers/receipts'
                );
            }

            $attachments = $transfer->attachments ?: [];
            foreach ($request->file('attachments', []) as $file) {
                $attachments[] = [
                    'path' => app(ManagedFileStorage::class)->uploadFile(
                        $file,
                        'sponsorship-transfers/attachments'
                    ),
                    'name' => $file->getClientOriginalName(),
                    'mime_type' => $file->getClientMimeType(),
                    'size' => $file->getSize(),
                ];
            }

            $oldSponsorId = (int) $transfer->contract_id === (int) $contract->id
                ? $transfer->old_sponsor_id
                : $contract->user_id;

            $transfer->update([
                'contract_id' => $contract->id,
                'old_sponsor_id' => $oldSponsorId,
                'new_sponsor_id' => $newSponsor->id,
                'trial_days' => $validated['trial_days'],
                'trial_start_date' => $validated['trial_start_date'],
                'contract_value' => $validated['contract_value'],
                'paid_amount' => $validated['paid_amount'],
                'remaining_amount' => $validated['contract_value'] - $validated['paid_amount'],
                'payment_type' => $validated['payment_type'],
                'receipt_account_id' => $validated['receipt_account_id'] ?? null,
                'payment_status' => $validated['payment_status'],
                'payment_receipt_path' => $paymentReceiptPath,
                'attachments' => $attachments ?: null,
                'payment_confirmed_at' => (float) $validated['paid_amount'] > 0 ? ($transfer->payment_confirmed_at ?? now()) : null,
                'payment_confirmed_by' => (float) $validated['paid_amount'] > 0 ? ($transfer->payment_confirmed_by ?? auth('admin')->id()) : null,
                'notes' => $validated['notes'] ?? null,
            ]);

            if (!$transfer->isRentalWorkflow() && (float) $transfer->paid_amount > 0 && !$transfer->receipt_bond_id) {
                $paymentService->createReceipt(
                    $transfer,
                    (int) $validated['receipt_account_id'],
                    now()
                );
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث طلب نقل الكفالة بنجاح'
            ]);

        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => collect($e->errors())->flatten()->first() ?? 'البيانات المدخلة غير صحيحة',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Error in SponsorshipTransfer update: ' . $e->getMessage(), [
                'exception' => get_class($e),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $this->exceptionMessage('حدث خطأ في تحديث طلب نقل الكفالة', $e),
                'debug' => $this->exceptionDebug($e),
            ], 500);
        }
    }

    private function exceptionMessage(string $baseMessage, \Throwable $e): string
    {
        if (!config('app.debug')) {
            return $baseMessage;
        }

        return $baseMessage . ': ' . $e->getMessage();
    }

    private function exceptionDebug(\Throwable $e): ?array
    {
        if (!config('app.debug')) {
            return null;
        }

        return [
            'exception' => get_class($e),
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
    }

    /**
     * Approve transfer request and start trial period
     */
    public function approve($id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن الموافقة على هذا الطلب'
                ], 400);
            }

            $previousStatus = $transfer->status;
            $transfer->update([
                'status' => 'trial',
                'approved_by' => auth()->guard('admin')->id(),
                'approved_at' => now(),
                'trial_started_at' => now(),
            ]);
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

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
            $this->assertCanAccessBranchModel($transfer);

            if ($transfer->status !== 'pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن رفض هذا الطلب'
                ], 400);
            }

            $previousStatus = $transfer->status;
            $transfer->update([
                'status' => 'rejected',
                'rejection_reason' => $request->rejection_reason ?? 'لم يتم تحديد سبب الرفض',
            ]);
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

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
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            if ($transfer->status !== 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إكمال هذا الطلب'
                ], 400);
            }

            $previousStatus = $transfer->status;
            $transfer->update([
                'status' => 'completed'
            ]);
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

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
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_DELETE), 403);

        try {
            $transfer = SponsorshipTransfer::findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if (!$transfer->canDeleteDetails()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن حذف هذا الطلب'
                ], 400);
            }

            $transfer->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف طلب نقل الكفالة من القائمة بنجاح'
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
        $search = trim((string) $request->input('search'));

        $clientsQuery = User::query()
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'LIKE', "%{$search}%")
                        ->orWhere('national_num', 'LIKE', "%{$search}%")
                        ->orWhere('phone', 'LIKE', "%{$search}%");
                });
            })
            ->select('id', 'name', 'national_num', 'client_type', 'tax_number', 'license_number', 'phone', 'phone_code', 'city')
            ->latest('id')
            ->limit(10)
            ;
        $this->applyRequestBranchFilter($clientsQuery, $request, 'users');
        $clients = $clientsQuery->get();

        $results = $clients->map(function ($client) {
            $phone = trim((string) (($client->phone_code ?? '') . $client->phone));

            return [
                'id' => $client->id,
                'text' => $client->name . ' - ' . ($client->searchableDocumentNumber() ?: 'بدون هوية') . ' - ' . ($phone ?: 'بدون جوال')
            ];
        });

        return response()->json(['results' => $results]);
    }

    private function contractIsRentalWorker(Contract $contract): bool
    {
        $contract->loadMissing('maid.jobTypes');
        $jobTypes = $contract->maid?->jobTypes ?? collect();

        if ($jobTypes->contains(fn ($jobType) => (int) $jobType->id === 2)) {
            return true;
        }

        $titles = mb_strtolower($jobTypes->pluck('title')->filter()->implode(' '));

        return str_contains($titles, 'إيجار')
            || str_contains($titles, 'ايجار')
            || str_contains($titles, 'تأجير')
            || str_contains($titles, 'rental')
            || str_contains($titles, 'rent');
    }

    private function findOrCreateNewSponsor(array $validated): User
    {
        $normalizedPhone = $this->normalizeSaudiPhone((string) $validated['new_sponsor_phone']);
        if (!$normalizedPhone) {
            throw ValidationException::withMessages([
                'new_sponsor_phone' => 'رقم الجوال غير صحيح. الصيغة المتاحة: 05XXXXXXXX أو 5XXXXXXXX أو +9665XXXXXXXX.',
            ]);
        }

        $nationalNum = trim((string) ($validated['new_sponsor_national_num'] ?? ''));
        $existingByPhone = User::where('phone', $normalizedPhone)->first();
        $existingByNationalNum = $nationalNum !== ''
            ? User::where('national_num', $nationalNum)->first()
            : null;

        if ($existingByPhone && $existingByNationalNum && (int) $existingByPhone->id !== (int) $existingByNationalNum->id) {
            throw ValidationException::withMessages([
                'new_sponsor_national_num' => 'رقم الهوية مرتبط بعميل آخر برقم جوال مختلف. اختر العميل الموجود من القائمة.',
            ]);
        }

        if ($existingByPhone) {
            $updates = [];

            if (empty($existingByPhone->name)) {
                $updates['name'] = trim((string) $validated['new_sponsor_name']);
            }

            if (empty($existingByPhone->phone_code)) {
                $updates['phone_code'] = '+966';
            }

            if (empty($existingByPhone->register_from)) {
                $updates['register_from'] = 'dashboard';
            }

            if ($nationalNum !== '' && blank($existingByPhone->documentNumber())) {
                $updates = $existingByPhone->clientTypeAwarePayload($updates, $nationalNum);
            }

            if (!empty($updates)) {
                $existingByPhone->update($updates);
                $existingByPhone->refresh();
            }

            return $existingByPhone;
        }

        if ($existingByNationalNum) {
            throw ValidationException::withMessages([
                'new_sponsor_national_num' => 'رقم الهوية مسجل مسبقًا لعميل آخر. اختر العميل الموجود من القائمة أو استخدم رقم جواله المسجل.',
            ]);
        }

        return User::create([
            'name' => trim((string) $validated['new_sponsor_name']),
            'phone_code' => '+966',
            'phone' => $normalizedPhone,
            'national_num' => $nationalNum !== '' ? $nationalNum : null,
            'password' => bcrypt(Str::random(12)),
            'register_from' => 'dashboard',
            'is_blocked' => 0,
        ]);
    }

    private function normalizeSaudiPhone(string $phone): ?string
    {
        $digits = preg_replace('/\D+/', '', $phone);
        if (!$digits) {
            return null;
        }

        if (str_starts_with($digits, '00966')) {
            $digits = substr($digits, 5);
        } elseif (str_starts_with($digits, '966')) {
            $digits = substr($digits, 3);
        }

        if (str_starts_with($digits, '05')) {
            $digits = substr($digits, 1);
        }

        return preg_match('/^5\d{8}$/', $digits) ? $digits : null;
    }

    /**
     * Confirm transfer - move to transferred status
     */
    public function confirmTransfer($id)
    {
        try {
            $transfer = SponsorshipTransfer::with(['contract.info', 'transferServiceContract'])->findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            if ($transfer->status !== 'trial_started' && $transfer->status !== 'trial') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تأكيد النقل لهذا الطلب'
                ], 400);
            }

            $previousStatus = $transfer->status;
            DB::beginTransaction();

            $confirmedAt = now();
            $contract = $transfer->contract;
            if ($contract) {
                $contract->update([
                    'user_id' => $transfer->new_sponsor_id,
                    // 'status' => ContractStatusEnum::Sari->value,
                    'status' => ContractStatusEnum::Completed->value,
                ]);

                $this->createContractStatusLog($contract, ContractStatusEnum::Completed->value);
            }

            if ($transfer->transferServiceContract && !in_array($transfer->transferServiceContract->status, ['completed', 'cancelled'], true)) {
                $transfer->transferServiceContract->update([
                    'status' => 'completed',
                    'completed_at' => $confirmedAt,
                    'completed_by' => auth()->guard('admin')->id(),
                ]);
            }

            $transfer->update([
                'status' => 'transferred',
                'transferred_by' => auth()->guard('admin')->id(),
                'transferred_at' => $confirmedAt,
            ]);

            DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

            try {
                app(SponsorshipTransferCompletionService::class)->record($transfer);
            } catch (\Throwable $accountingEx) {
                \Log::warning('SponsorshipTransferCompletionService: ' . $accountingEx->getMessage(), ['transfer_id' => $transfer->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم تأكيد نقل الكفالة بنجاح'
            ]);

        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تأكيد النقل'
            ], 500);
        }
    }

    /**
     * Return a successfully transferred maid to shelter under her current sponsor.
     */
    public function returnTransferredMaidToShelter($id)
    {
        try {
            DB::beginTransaction();

            $transfer = SponsorshipTransfer::query()
                ->with(['contract.info', 'contract.maid'])
                ->lockForUpdate()
                ->findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($transfer->status !== 'transferred') {
                throw ValidationException::withMessages([
                    'transfer' => 'يمكن إعادة العاملة إلى الإيواء بعد اكتمال نقل الكفالة فقط.',
                ]);
            }

            if (!$transfer->contract) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يوجد عقد مرتبط بحركة نقل الكفالة.',
                ]);
            }

            $contract = Contract::query()
                ->with(['info', 'maid'])
                ->lockForUpdate()
                ->findOrFail($transfer->contract_id);

            if ((int) $contract->user_id !== (int) $transfer->new_sponsor_id) {
                throw ValidationException::withMessages([
                    'contract' => 'تعذر الإرجاع لأن الكفيل الحالي للعقد تغيّر بعد هذه الحركة.',
                ]);
            }

            if ($contract->status === ContractStatusEnum::Shelter->value
                && !$contract->info?->is_outside_shelter) {
                throw ValidationException::withMessages([
                    'contract' => 'العاملة موجودة داخل الإيواء بالفعل.',
                ]);
            }

            if ($contract->status !== ContractStatusEnum::Completed->value) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يمكن إعادة العاملة لأن حالة العقد الحالية لا تسمح بذلك.',
                ]);
            }

            $hasLaterTransfer = SponsorshipTransfer::query()
                ->where('contract_id', $contract->id)
                ->where('id', '>', $transfer->id)
                ->exists();

            if ($hasLaterTransfer) {
                throw ValidationException::withMessages([
                    'transfer' => 'توجد حركة نقل كفالة أحدث لهذه العاملة؛ لا يمكن الإرجاع من الحركة السابقة.',
                ]);
            }

            if ($contract->hasEscapeReport()) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يمكن إعادة العاملة إلى الإيواء لوجود بلاغ هروب عليها.',
                ]);
            }

            $hasActiveRental = $contract->maid?->rentalContracts()
                ->whereIn('status', \App\Services\RentalShelterPresenceService::OUTSIDE_RENTAL_STATUSES)
                ->exists() ?? false;

            if ($hasActiveRental) {
                throw ValidationException::withMessages([
                    'contract' => 'لا يمكن إعادة العاملة إلى الإيواء لوجود عقد إيجار نشط عليها.',
                ]);
            }

            $returnedAt = now();
            $contract->update([
                'status' => ContractStatusEnum::Shelter->value,
            ]);

            $contract->info()->updateOrCreate([], [
                'wants_to_work' => 'yes',
                'maid_date_back' => $returnedAt->toDateString(),
                'is_outside_shelter' => false,
                'outside_shelter_at' => null,
                'outside_shelter_reason' => null,
                'outside_shelter_reference_type' => null,
                'outside_shelter_reference_id' => null,
                'outside_shelter_by' => null,
            ]);

            if ($contract->maid) {
                $contract->maid->update([
                    'status' => 'available',
                    'available' => true,
                    'available_at' => $returnedAt->toDateString(),
                    'maid_accommodation_type' => 2,
                ]);
            }

            $contract->contract_status_logs()->create([
                'status' => ContractStatusEnum::Shelter->value,
                'date' => $returnedAt,
                'notes' => 'تمت إعادة العاملة إلى داخل الإيواء بعد اكتمال نقل الكفالة رقم ' . $transfer->id
                    . '، مع إبقاء الكفيل الحالي تمهيداً لإمكانية نقلها إلى كفيل آخر.',
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تمت إعادة العاملة إلى الإيواء وأصبحت متاحة لنقل الكفالة من الكفيل الحالي إلى كفيل آخر.',
                'redirect' => route('ShelterContract.availableForTransfer'),
            ]);
        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        } catch (\Throwable $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Error returning transferred maid to shelter', [
                'transfer_id' => $id,
                'admin_id' => auth()->guard('admin')->id(),
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء إعادة العاملة إلى الإيواء.',
            ], 500);
        }
    }

    /**
     * Cancel transfer - move to failed status and return to shelter
     */
    public function cancelTransfer(Request $request, SponsorshipTransferPaymentService $paymentService, $id)
    {
        $validated = $request->validate([
            'failure_reason' => 'nullable|string|max:1000',
            'failure_settlement_type' => 'nullable|in:refund',
            'failure_settlement_amount' => 'nullable|numeric|min:0',
            'failure_clear_remaining_amount' => 'nullable|numeric|min:0',
            'failure_client_iban' => 'nullable|string|max:34',
            'failure_client_iban_attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'refund_account_id' => 'nullable|integer|exists:fn_accounts,id',
        ], [
            'failure_client_iban_attachment.mimes' => 'مرفق الآيبان يجب أن يكون pdf أو jpg أو jpeg أو png',
            'failure_client_iban_attachment.max' => 'حجم مرفق الآيبان يجب ألا يتجاوز 4 ميجابايت',
        ]);

        try {
            $transfer = SponsorshipTransfer::with(['contract.info', 'transferServiceContract'])->findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($transfer->status !== 'trial_started' && $transfer->status !== 'trial') {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إلغاء النقل لهذا الطلب'
                ], 400);
            }

            $previousStatus = $transfer->status;
            DB::beginTransaction();

            $failedAt = now();
            $failureReason = trim((string) ($validated['failure_reason'] ?? ''));
            if ($failureReason === '') {
                $failureReason = 'لم يتم تحديد سبب الإلغاء';
            }

            $settlementType = $validated['failure_settlement_type'] ?? 'refund';
            $settlementAmount = round((float) ($validated['failure_settlement_amount'] ?? $transfer->paid_amount), 2);
            $clearRemainingAmount = round((float) ($validated['failure_clear_remaining_amount'] ?? $transfer->remaining_amount), 2);
            $paidAmount = round((float) $transfer->paid_amount, 2);
            $remainingAmount = round((float) $transfer->remaining_amount, 2);

            if ($settlementAmount > $paidAmount) {
                throw ValidationException::withMessages([
                    'failure_settlement_amount' => 'مبلغ الاسترجاع لا يمكن أن يكون أكبر من المبلغ المدفوع.',
                ]);
            }

            if ($settlementAmount > 0 && empty($validated['refund_account_id'])) {
                throw ValidationException::withMessages([
                    'refund_account_id' => 'يجب اختيار البنك أو حساب السداد لإصدار سند الصرف.',
                ]);
            }

            if ($clearRemainingAmount > $remainingAmount) {
                throw ValidationException::withMessages([
                    'failure_clear_remaining_amount' => 'مبلغ تصفير المتبقي لا يمكن أن يكون أكبر من المبلغ المتبقي.',
                ]);
            }

            if ($settlementAmount > 0 && empty($validated['failure_client_iban']) && !$request->hasFile('failure_client_iban_attachment')) {
                throw ValidationException::withMessages([
                    'failure_client_iban' => 'عند وجود مبلغ استرجاع يجب إدخال آيبان العميل أو إرفاق مرفق الآيبان.',
                ]);
            }

            $ibanAttachmentPath = $request->hasFile('failure_client_iban_attachment')
                ? app(ManagedFileStorage::class)->uploadFile(
                    $request->file('failure_client_iban_attachment'),
                    'sponsorship-transfers/failure-settlements/iban'
                )
                : null;

            $contract = $transfer->contract;
            if ($contract) {
                $contract->update([
                    'status' => ContractStatusEnum::Shelter->value,
                ]);

                $this->returnShelterContractInsideForTransfer($transfer, $failureReason);
            }

            if ($transfer->transferServiceContract && $transfer->transferServiceContract->status !== 'completed') {
                $transfer->transferServiceContract->update([
                    'status' => 'cancelled',
                    'cancelled_at' => $failedAt,
                    'cancelled_by' => auth()->guard('admin')->id(),
                    'cancellation_reason' => $failureReason,
                ]);
            }

            $transfer->update([
                'status' => 'failed',
                'failed_by' => auth()->guard('admin')->id(),
                'failed_at' => $failedAt,
                'failure_reason' => $failureReason,
                'failure_settlement_type' => $settlementType,
                'failure_settlement_amount' => $settlementAmount,
                'failure_clear_remaining_amount' => $clearRemainingAmount,
                'failure_client_iban' => $validated['failure_client_iban'] ?? null,
                'failure_client_iban_attachment_path' => $ibanAttachmentPath,
                'remaining_amount' => max(0, $remainingAmount - $clearRemainingAmount),
            ]);

            $refundBond = null;
            if ($settlementAmount > 0) {
                $refundResult = $paymentService->createRefund(
                    $transfer,
                    $settlementAmount,
                    (int) $validated['refund_account_id'],
                    $failedAt
                );
                $refundBond = $refundResult['bond'] ?? null;
            }

            DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

            try {
                app(SponsorshipTransferFailureService::class)->record($transfer);
            } catch (\Throwable $accountingEx) {
                \Log::warning('SponsorshipTransferFailureService: ' . $accountingEx->getMessage(), ['transfer_id' => $transfer->id]);
            }

            return response()->json([
                'success' => true,
                'message' => 'تم إلغاء النقل وإعادة العقد إلى الإيواء وتسجيل مبلغ الاسترجاع للعميل'
                    . ($refundBond ? ' وإنشاء سند صرف رقم ' . ($refundBond->code ?: $refundBond->id) : '')
            ]);

        } catch (ValidationException $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        } catch (\Exception $e) {
            if (DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            \Log::error('Error cancelling sponsorship transfer', [
                'transfer_id' => $id,
                'admin_id' => auth()->guard('admin')->id(),
                'exception' => $e,
            ]);

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
        abort_unless(@AdminGuardHasPerm(\App\PermKeys::SPONSOR_TRANSFER_EDIT), 403);

        try {
            $order = Order::findOrFail($id);
            $this->assertCanAccessBranchModel($order);

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
    public function trial(Request $request)
    {
        return $this->renderStatusListingPage($request, 'trial');
    }

    /**
     * Show completed transfers
     */
    public function transferred(Request $request)
    {
        return $this->renderStatusListingPage($request, 'transferred');
    }

    /**
     * Show failed transfers
     */
    public function failed(Request $request)
    {
        return $this->renderStatusListingPage($request, 'failed');
    }

    private function renderStatusListingPage(Request $request, string $page)
    {
        $pageConfig = $this->getStatusListingConfig($page);

        $baseQuery = SponsorshipTransfer::query()
            ->whereIn('status', $pageConfig['statuses']);
        $this->applyRequestBranchFilter($baseQuery, $request, 'sponsorship_transfers');

        $this->applyStatusListingFilters($baseQuery, $request, $page);

        $stats = $this->buildStatusListingStats(clone $baseQuery, $page);
        $topFailureReasons = $page === 'failed'
            ? $this->getTopFailureReasons(clone $baseQuery)
            : collect();

        $transfersQuery = (clone $baseQuery)
            ->with($pageConfig['relations']);

        $this->applyStatusListingSort($transfersQuery, $request, $page);

        $transfers = $transfersQuery
            ->paginate(25)
            ->withQueryString();

        $filtersActive = collect($request->except('page'))
            ->filter(fn ($value) => filled($value))
            ->isNotEmpty();

        // حسابات الصرف مطلوبة في نافذة إلغاء النقل (فترة التجربة) لإصدار سند الصرف
        $receiptAccounts = $page === 'trial'
            ? SponsorshipTransferPaymentService::receiptAccounts()
            : collect();

        return view($this->folderPath . '.status-overview', compact(
            'transfers',
            'pageConfig',
            'stats',
            'topFailureReasons',
            'filtersActive',
            'receiptAccounts'
        ));
    }

    private function getStatusListingConfig(string $page): array
    {
        return match ($page) {
            'trial' => [
                'page' => 'trial',
                'title' => helperTrans('ingaz.trial_period'),
                'route' => route('SponsorshipTransfer.trial'),
                'statuses' => ['trial', 'trial_started'],
                'relations' => ['contract.maid.nationality', 'oldSponsor', 'newSponsor'],
                'theme' => 'primary',
                'date_label' => 'بداية التجربة',
                'empty_icon' => 'fas fa-hourglass-half',
                'empty_title' => 'لا توجد طلبات في فترة التجربة',
                'empty_message' => 'عند بدء فترة التجربة لطلبات نقل الكفالة ستظهر هنا.',
                'sort_options' => [
                    'trial_end_date' => 'نهاية التجربة',
                    'trial_start_date' => 'بداية التجربة',
                    'trial_days' => 'مدة التجربة',
                    'remaining_amount' => 'المبلغ المتبقي',
                    'contract_value' => 'قيمة العقد',
                    'created_at' => 'تاريخ الطلب',
                ],
                'default_sort' => 'trial_end_date',
                'default_direction' => 'asc',
            ],
            'transferred' => [
                'page' => 'transferred',
                'title' => helperTrans('ingaz.transferred'),
                'route' => route('SponsorshipTransfer.transferred'),
                'statuses' => ['transferred'],
                'relations' => ['contract.info', 'contract.maid.nationality', 'oldSponsor', 'newSponsor', 'transferredBy'],
                'theme' => 'success',
                'date_label' => 'تاريخ النقل',
                'empty_icon' => 'fas fa-check-circle',
                'empty_title' => 'لا توجد طلبات تم نقلها',
                'empty_message' => 'عند تأكيد نقل الكفالة ستظهر الطلبات هنا.',
                'sort_options' => [
                    'status_date' => 'تاريخ النقل',
                    'contract_value' => 'قيمة العقد',
                    'paid_amount' => 'المبلغ المدفوع',
                    'created_at' => 'تاريخ الطلب',
                ],
                'default_sort' => 'status_date',
                'default_direction' => 'desc',
            ],
            'failed' => [
                'page' => 'failed',
                'title' => helperTrans('ingaz.trial_failed'),
                'route' => route('SponsorshipTransfer.failed'),
                'statuses' => ['failed'],
                'relations' => ['contract.maid.nationality', 'oldSponsor', 'newSponsor', 'failedBy'],
                'theme' => 'danger',
                'date_label' => 'تاريخ الفشل',
                'empty_icon' => 'fas fa-times-circle',
                'empty_title' => 'لا توجد حالات فشل نقل',
                'empty_message' => 'عند إلغاء نقل الكفالة وإرجاع العقد إلى الإيواء ستظهر الحالات هنا.',
                'sort_options' => [
                    'status_date' => 'تاريخ الفشل',
                    'contract_value' => 'قيمة العقد',
                    'failure_settlement_amount' => 'المستحق للعميل',
                    'created_at' => 'تاريخ الطلب',
                ],
                'default_sort' => 'status_date',
                'default_direction' => 'desc',
            ],
        };
    }

    private function applyStatusListingFilters($query, Request $request, string $page): void
    {
        if ($request->filled('contract_number')) {
            $query->where('contract_id', 'like', '%' . trim((string) $request->contract_number) . '%');
        }

        if ($request->filled('old_sponsor')) {
            $keyword = trim((string) $request->old_sponsor);
            $query->whereHas('oldSponsor', function ($sponsorQuery) use ($keyword) {
                $sponsorQuery->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('national_num', 'like', '%' . $keyword . '%');
                });
            });
        }

        if ($request->filled('new_sponsor')) {
            $keyword = trim((string) $request->new_sponsor);
            $query->whereHas('newSponsor', function ($sponsorQuery) use ($keyword) {
                $sponsorQuery->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('phone', 'like', '%' . $keyword . '%')
                        ->orWhere('national_num', 'like', '%' . $keyword . '%');
                });
            });
        }

        if ($request->filled('maid_keyword')) {
            $keyword = trim((string) $request->maid_keyword);
            $query->whereHas('contract.maid', function ($maidQuery) use ($keyword) {
                $maidQuery->where(function ($innerQuery) use ($keyword) {
                    $innerQuery->where('name', 'like', '%' . $keyword . '%')
                        ->orWhere('passport_num', 'like', '%' . $keyword . '%');
                });
            });
        }

        if ($request->filled('trial_days')) {
            $query->where('trial_days', (int) $request->trial_days);
        }

        if ($request->filled('amount_from') && is_numeric($request->amount_from)) {
            $query->where('contract_value', '>=', (float) $request->amount_from);
        }

        if ($request->filled('amount_to') && is_numeric($request->amount_to)) {
            $query->where('contract_value', '<=', (float) $request->amount_to);
        }

        if ($request->filled('date_from')) {
            $query->whereRaw($this->getStatusFilterDateExpression($page) . ' >= ?', [$request->date_from]);
        }

        if ($request->filled('date_to')) {
            $query->whereRaw($this->getStatusFilterDateExpression($page) . ' <= ?', [$request->date_to]);
        }

        if ($page === 'trial' && $request->filled('trial_state')) {
            $today = now()->toDateString();
            $nextWeek = now()->addDays(7)->toDateString();
            $trialEndDateExpression = $this->getTrialEndDateExpression();

            match ($request->trial_state) {
                'active' => $query->whereRaw('DATE(' . $trialEndDateExpression . ') >= ?', [$today]),
                'today' => $query->whereRaw('DATE(' . $trialEndDateExpression . ') = ?', [$today]),
                'soon' => $query->whereRaw('DATE(' . $trialEndDateExpression . ') BETWEEN ? AND ?', [$today, $nextWeek]),
                'expired' => $query->whereRaw('DATE(' . $trialEndDateExpression . ') < ?', [$today]),
                default => null,
            };
        }

        if ($page === 'failed' && $request->filled('failure_reason')) {
            $reason = trim((string) $request->failure_reason);
            $query->where('failure_reason', 'like', '%' . $reason . '%');
        }

        if ($page === 'failed' && $request->boolean('returned_to_shelter')) {
            $query->whereHas('contract', function ($contractQuery) {
                $contractQuery->where('status', ContractStatusEnum::Shelter->value);
            });
        }

        if ($page === 'failed' && $request->boolean('documented_reason')) {
            $query->whereNotNull('failure_reason')
                ->where('failure_reason', '!=', '');
        }
    }

    private function applyStatusListingSort($query, Request $request, string $page): void
    {
        $pageConfig = $this->getStatusListingConfig($page);
        $sort = (string) $request->input('sort', $pageConfig['default_sort']);
        $direction = strtolower((string) $request->input('direction', $pageConfig['default_direction']));
        $direction = in_array($direction, ['asc', 'desc'], true) ? $direction : $pageConfig['default_direction'];

        switch ($sort) {
            case 'trial_end_date':
                $query->orderByRaw($this->getTrialEndDateExpression() . ' ' . $direction);
                break;
            case 'trial_start_date':
                $query->orderByRaw($this->getStatusDateOrderExpression('trial') . ' ' . $direction);
                break;
            case 'status_date':
                $query->orderByRaw($this->getStatusDateOrderExpression($page) . ' ' . $direction);
                break;
            case 'trial_days':
            case 'remaining_amount':
            case 'contract_value':
            case 'paid_amount':
            case 'failure_settlement_amount':
            case 'created_at':
                $query->orderBy($sort, $direction);
                break;
            default:
                $query->orderByRaw($this->getStatusDateOrderExpression($page) . ' ' . $pageConfig['default_direction']);
                break;
        }

        $query->orderBy('id', 'desc');
    }

    private function buildStatusListingStats($query, string $page): array
    {
        return match ($page) {
            'trial' => $this->buildTrialStats($query),
            'transferred' => $this->buildTransferredStats($query),
            'failed' => $this->buildFailedStats($query),
            default => [],
        };
    }

    private function buildTrialStats($query): array
    {
        $transfers = (clone $query)->get([
            'id',
            'trial_started_at',
            'trial_start_date',
            'trial_days',
            'remaining_amount',
        ]);

        $activeCount = $transfers->filter(fn ($transfer) => $transfer->trial_days_remaining !== null && $transfer->trial_days_remaining >= 0)->count();
        $endingSoonCount = $transfers->filter(fn ($transfer) => $transfer->trial_days_remaining !== null && $transfer->trial_days_remaining >= 0 && $transfer->trial_days_remaining <= 7)->count();
        $expiredCount = $transfers->filter(fn ($transfer) => $transfer->trial_days_remaining !== null && $transfer->trial_days_remaining < 0)->count();

        return [
            ['label' => 'إجمالي الطلبات', 'value' => number_format($transfers->count()), 'icon' => 'fas fa-list', 'theme' => 'primary', 'href' => $this->buildStatusPageLink('trial', [], ['trial_state'])],
            ['label' => 'ضمن مدة التجربة', 'value' => number_format($activeCount), 'icon' => 'fas fa-play-circle', 'theme' => 'success', 'href' => $this->buildStatusPageLink('trial', ['trial_state' => 'active'])],
            ['label' => 'تنتهي خلال 7 أيام', 'value' => number_format($endingSoonCount), 'icon' => 'fas fa-hourglass-half', 'theme' => 'warning', 'href' => $this->buildStatusPageLink('trial', ['trial_state' => 'soon'])],
            ['label' => 'منتهية', 'value' => number_format($expiredCount), 'icon' => 'fas fa-exclamation-circle', 'theme' => 'danger', 'href' => $this->buildStatusPageLink('trial', ['trial_state' => 'expired'])],
        ];
    }

    private function buildTransferredStats($query): array
    {
        $monthStart = now()->startOfMonth();

        return [
            ['label' => 'إجمالي المنقول', 'value' => number_format((clone $query)->count()), 'icon' => 'fas fa-check-circle', 'theme' => 'success', 'href' => $this->buildStatusPageLink('transferred', [], ['date_from', 'date_to'])],
            ['label' => 'منقول هذا الشهر', 'value' => number_format((clone $query)->where('transferred_at', '>=', $monthStart)->count()), 'icon' => 'fas fa-calendar-alt', 'theme' => 'primary', 'href' => $this->buildStatusPageLink('transferred', ['date_from' => $monthStart->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()])],
            ['label' => 'إجمالي قيمة العقود', 'value' => number_format((float) (clone $query)->sum('contract_value'), 2) . ' ريال', 'icon' => 'fas fa-money-bill-wave', 'theme' => 'warning', 'href' => $this->buildStatusPageLink('transferred', ['sort' => 'contract_value', 'direction' => 'desc'])],
            ['label' => 'إجمالي المدفوع', 'value' => number_format((float) (clone $query)->sum('paid_amount'), 2) . ' ريال', 'icon' => 'fas fa-wallet', 'theme' => 'info', 'href' => $this->buildStatusPageLink('transferred', ['sort' => 'paid_amount', 'direction' => 'desc'])],
        ];
    }

    private function buildFailedStats($query): array
    {
        $monthStart = now()->startOfMonth();
        $returnedToShelterCount = (clone $query)
            ->whereHas('contract', function ($contractQuery) {
                $contractQuery->where('status', ContractStatusEnum::Shelter->value);
            })
            ->count();

        $documentedReasonCount = (clone $query)
            ->whereNotNull('failure_reason')
            ->where('failure_reason', '!=', '')
            ->count();

        return [
            ['label' => 'إجمالي الفشل', 'value' => number_format((clone $query)->count()), 'icon' => 'fas fa-times-circle', 'theme' => 'danger', 'href' => $this->buildStatusPageLink('failed', [], ['date_from', 'date_to', 'returned_to_shelter', 'documented_reason'])],
            ['label' => 'فشل هذا الشهر', 'value' => number_format((clone $query)->where('failed_at', '>=', $monthStart)->count()), 'icon' => 'fas fa-calendar-alt', 'theme' => 'primary', 'href' => $this->buildStatusPageLink('failed', ['date_from' => $monthStart->toDateString(), 'date_to' => now()->endOfMonth()->toDateString()])],
            ['label' => 'عادت للإيواء', 'value' => number_format($returnedToShelterCount), 'icon' => 'fas fa-home', 'theme' => 'warning', 'href' => $this->buildStatusPageLink('failed', ['returned_to_shelter' => '1'])],
            ['label' => 'بسبب موثق', 'value' => number_format($documentedReasonCount), 'icon' => 'fas fa-file-alt', 'theme' => 'info', 'href' => $this->buildStatusPageLink('failed', ['documented_reason' => '1'])],
        ];
    }

    private function getTopFailureReasons($query)
    {
        return (clone $query)
            ->select('failure_reason', DB::raw('COUNT(*) as total'))
            ->whereNotNull('failure_reason')
            ->where('failure_reason', '!=', '')
            ->groupBy('failure_reason')
            ->orderByDesc('total')
            ->limit(5)
            ->get();
    }

    private function getStatusFilterDateExpression(string $page): string
    {
        return match ($page) {
            'trial' => 'DATE(COALESCE(trial_started_at, trial_start_date))',
            'transferred' => 'DATE(transferred_at)',
            'failed' => 'DATE(failed_at)',
            default => 'DATE(created_at)',
        };
    }

    private function getStatusDateOrderExpression(string $page): string
    {
        return match ($page) {
            'trial' => 'COALESCE(trial_started_at, trial_start_date)',
            'transferred' => 'transferred_at',
            'failed' => 'failed_at',
            default => 'created_at',
        };
    }

    private function getTrialEndDateExpression(): string
    {
        return 'DATE_ADD(COALESCE(trial_started_at, trial_start_date), INTERVAL trial_days DAY)';
    }

    private function createContractStatusLog(Contract $contract, string $status): void
    {
        $contract->contract_status_logs()->create([
            'status' => $status,
        ]);
    }

    private function buildStatusPageLink(string $page, array $overrides = [], array $remove = []): string
    {
        $pageConfig = $this->getStatusListingConfig($page);
        $query = request()->except('page');

        foreach ($remove as $key) {
            unset($query[$key]);
        }

        $query = array_merge($query, $overrides);
        $query = array_filter($query, fn ($value) => !is_null($value) && $value !== '');

        return empty($query)
            ? $pageConfig['route']
            : $pageConfig['route'] . '?' . http_build_query($query);
    }

    /**
     * إرسال العقد للعميل
     */
    public function sendContract(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            // التحقق من الحالة
            if (!$transfer->canSendContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن إرسال العقد في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            $previousStatus = $transfer->status;

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'contract_sent',
                'contract_sent_at' => now(),
                'contract_sent_by' => auth('admin')->id()
            ]);

            \DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

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
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            // التحقق من الحالة
            if (!$transfer->canSignContract()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسجيل توقيع العقد في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            $previousStatus = $transfer->status;

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'contract_signed',
                'contract_signed_at' => now(),
                'contract_signed_by' => auth('admin')->id()
            ]);


            \DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

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
            $transfer = SponsorshipTransfer::with(['transferServiceContract', 'contract'])->findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

            // التحقق من الحالة
            if (!$transfer->canDeliverMaid()) {
                return response()->json([
                    'success' => false,
                    'message' => 'لا يمكن تسجيل تسليم العاملة في الحالة الحالية'
                ], 400);
            }

            \DB::beginTransaction();

            $activatedAt = now();
            $previousStatus = $transfer->status;

            // تسجيل استلام العاملة وتفعيل التجربة مباشرة
            $transfer->update([
                'status' => 'trial_started',
                'maid_delivered_at' => $activatedAt,
                'maid_delivered_by' => auth('admin')->id(),
                'trial_started_at' => $activatedAt,
                'trial_start_date' => $activatedAt->toDateString(),
            ]);

            if ($transfer->contract) {
                $transfer->contract->update([
                    'status' => ContractStatusEnum::Trial->value
                ]);

                $this->markShelterContractOutsideForTransfer($transfer);
            }

            if ($transfer->transferServiceContract && !in_array($transfer->transferServiceContract->status, ['completed', 'cancelled'], true)) {
                $transfer->transferServiceContract->update([
                    'status' => 'active',
                    'activated_at' => $activatedAt,
                    'activated_by' => auth('admin')->id(),
                    'trial_start_date' => $activatedAt->toDateString(),
                ]);
            }

            \DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

            $accountingWarning = $this->recordTrialStartConstraint($transfer);
            $message = 'تم تسجيل استلام العاملة وتفعيل فترة التجربة مباشرة';
            if ($accountingWarning) {
                $message .= "\n\n" . $accountingWarning;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'accounting_warning' => $accountingWarning,
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
     * يحاول إنشاء قيد بداية التجربة دون تعطيل الإجراء التشغيلي عند فشل المحاسبة.
     */
    private function recordTrialStartConstraint(SponsorshipTransfer $transfer): ?string
    {
        try {
            $constraint = app(SponsorshipTransferTrialStartService::class)->record($transfer);

            if (!$constraint) {
                return 'تنبيه محاسبي: بدأت فترة التجربة، لكن لم يتم إنشاء قيد بداية التجربة دون سبب محدد.';
            }

            return null;
        } catch (\Throwable $accountingEx) {
            \Log::warning('SponsorshipTransferTrialStartService: ' . $accountingEx->getMessage(), [
                'transfer_id' => $transfer->id,
            ]);

            return 'تنبيه محاسبي: بدأت فترة التجربة، لكن لم يتم إنشاء قيد بداية التجربة. السبب: '
                . $accountingEx->getMessage();
        }
    }

    private function markShelterContractOutsideForTransfer(SponsorshipTransfer $transfer): void
    {
        if (!$transfer->contract) {
            return;
        }

        $transfer->contract->info()->updateOrCreate([], [
            'is_outside_shelter' => true,
            'outside_shelter_at' => $transfer->maid_delivered_at ?: now(),
            'outside_shelter_reason' => 'transfer_delivery',
            'outside_shelter_reference_type' => SponsorshipTransfer::class,
            'outside_shelter_reference_id' => $transfer->id,
            'outside_shelter_by' => auth('admin')->id() ?: auth()->id(),
        ]);

        $transfer->contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Trial->value,
            'date' => now(),
            'notes' => 'تم إخراج العاملة من داخل الإيواء بسبب بدء فترة تجربة نقل خدمات رقم ' . $transfer->id,
        ]);
    }

    private function notifySponsorshipTransferStatusChanged(SponsorshipTransfer $transfer, ?string $previousStatus): void
    {
        if ((string) $previousStatus === (string) $transfer->status) {
            return;
        }

        $recipients = collect([
            $transfer->payment_confirmed_by,
            $transfer->approved_by,
            $transfer->contract_sent_by,
            $transfer->contract_signed_by,
            $transfer->maid_delivered_by,
            $transfer->transferred_by,
            $transfer->failed_by,
        ])->filter()->unique()->values();

        if ($recipients->isEmpty()) {
            return;
        }

        app(AdminNotificationService::class)->sendToAdmins(
            $recipients,
            'تم تحديث نقل الكفالة #' . $transfer->id,
            'تغيرت حالة نقل الكفالة من "' . $this->sponsorshipTransferStatusLabel($previousStatus) . '" إلى "' . $transfer->status_text . '".',
            'sponsorship_transfer_status_changed',
            route('SponsorshipTransfer.show', $transfer->id),
            [
                'model' => SponsorshipTransfer::class,
                'model_id' => $transfer->id,
                'old_status' => $previousStatus,
                'new_status' => $transfer->status,
            ]
        );
    }

    private function notifySponsorshipTransferCreated(SponsorshipTransfer $transfer): void
    {
        $recipientIds = $this->sponsorshipTransferNotificationAdminIds($transfer);

        if ($recipientIds->isEmpty()) {
            return;
        }

        app(AdminNotificationService::class)->sendToAdmins(
            $recipientIds,
            'طلب نقل خدمات جديد #' . $transfer->id,
            'تم إنشاء طلب نقل خدمات جديد من "' . ($transfer->oldSponsor?->name ?? 'كفيل غير محدد') . '" إلى "' . ($transfer->newSponsor?->name ?? 'كفيل غير محدد') . '".',
            'sponsorship_transfer_created',
            route('SponsorshipTransfer.show', $transfer->id),
            [
                'model' => SponsorshipTransfer::class,
                'model_id' => $transfer->id,
                'contract_id' => $transfer->contract_id,
                'old_sponsor_id' => $transfer->old_sponsor_id,
                'new_sponsor_id' => $transfer->new_sponsor_id,
                'status' => $transfer->status,
            ]
        );
    }

    private function sponsorshipTransferNotificationAdminIds(SponsorshipTransfer $transfer)
    {
        $branchesEnabled = SystemModuleManager::isEnabled('branches')
            && Schema::hasColumn('admins', 'branch_id')
            && Schema::hasColumn('sponsorship_transfers', 'branch_id');
        $transferBranchId = (int) ($transfer->branch_id ?? 0);

        return Admin::query()
            ->activeForOperations()
            ->get()
            ->filter(function (Admin $admin) use ($branchesEnabled, $transferBranchId) {
                if (! $admin->hasAnyPermission([
                    PermKeys::SPONSOR_TRANSFER_VIEW,
                    PermKeys::SPONSOR_TRANSFER_CREATE,
                    PermKeys::SPONSOR_TRANSFER_EDIT,
                    PermKeys::SPONSOR_TRANSFER_CONTROL,
                ])) {
                    return false;
                }

                if (! $branchesEnabled || $transferBranchId < 1) {
                    return true;
                }

                $adminBranchId = (int) ($admin->branch_id ?? 0);

                return $adminBranchId < 1 || $adminBranchId === $transferBranchId;
            })
            ->pluck('id')
            ->values();
    }

    private function sponsorshipTransferStatusLabel(?string $status): string
    {
        return [
            'pending' => 'في الانتظار',
            'waiting_contract_send' => 'بانتظار تسجيل استلام العاملة',
            'contract_sent' => 'تم إرسال العقد',
            'contract_signed' => 'تم توقيع العقد',
            'maid_delivered' => 'تم تسليم العاملة',
            'trial' => 'فترة التجربة',
            'trial_started' => 'بدأت التجربة',
            'approved' => 'موافق عليه',
            'transferred' => 'تم النقل',
            'failed' => 'فشل النقل',
            'rejected' => 'مرفوض',
            'completed' => 'مكتمل',
        ][$status] ?? ($status ?: 'غير محدد');
    }

    private function returnShelterContractInsideForTransfer(SponsorshipTransfer $transfer, ?string $reason = null): void
    {
        if (!$transfer->contract) {
            return;
        }

        $contract = $transfer->contract;
        $contract->loadMissing('info');

        $contract->info()->updateOrCreate([], [
            'wants_to_work' => 'yes',
            'is_outside_shelter' => false,
            'outside_shelter_at' => null,
            'outside_shelter_reason' => null,
            'outside_shelter_reference_type' => null,
            'outside_shelter_reference_id' => null,
            'outside_shelter_by' => null,
        ]);

        $contract->contract_status_logs()->create([
            'status' => ContractStatusEnum::Shelter->value,
            'date' => now(),
            'notes' => 'تمت إعادة العاملة إلى داخل الإيواء بعد فشل/إلغاء نقل خدمات رقم ' . $transfer->id
                . ($reason ? ' - السبب: ' . $reason : ''),
        ]);
    }

    /**
     * بدء فترة التجربة
     */
    public function startTrial(Request $request, $id)
    {
        try {
            $transfer = SponsorshipTransfer::with(['transferServiceContract', 'contract'])->findOrFail($id);
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

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

            $startedAt = now();
            $previousStatus = $transfer->status;

            // تحديث حالة طلب النقل
            $transfer->update([
                'status' => 'trial_started',
                'trial_started_at' => $startedAt,
                'trial_start_date' => $startedAt->toDateString(),
            ]);

            // تحديث حالة العقد الأصلي إلى "في التجربة"
            if ($transfer->contract) {
                $transfer->contract->update([
                    'status' => ContractStatusEnum::Trial->value
                ]);

                $this->createContractStatusLog($transfer->contract, ContractStatusEnum::Trial->value);
            }

            if ($transfer->transferServiceContract && !in_array($transfer->transferServiceContract->status, ['completed', 'cancelled'], true)) {
                $transfer->transferServiceContract->update([
                    'status' => 'active',
                    'activated_at' => $startedAt,
                    'activated_by' => auth('admin')->id(),
                    'trial_start_date' => $startedAt->toDateString(),
                ]);
            }

            \DB::commit();
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

            $accountingWarning = $this->recordTrialStartConstraint($transfer);
            $message = 'تم بدء فترة التجربة بنجاح';
            if ($accountingWarning) {
                $message .= "\n\n" . $accountingWarning;
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'accounting_warning' => $accountingWarning,
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
        $this->assertCanAccessBranchModel($transfer);

        // جلب بنود العقد
        $contractTerms = \App\Models\TransferContractTerm::active()
            ->ordered()
            ->get()
            ->groupBy('page_number');

        $documentBranding = documentBranding();

        // إعدادات العقد (يمكن تخزينها في جدول منفصل لاحقاً)
        $contractSettings = [
            'company_name' => $documentBranding['company_name'],
            'license_number' => '1234567890',
            'address' => documentSetting('location', 'الرياض، المملكة العربية السعودية'),
            'phone' => documentSetting('phone', '+966 11 123 4567'),
            'email' => documentSetting('email', 'info@ingaz.com'),
            'logo' => documentSetting('document_logo'),
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
        $this->assertCanAccessBranchModel($transfer);

        // التحقق من الحالة
        if (!in_array($transfer->status, ['maid_delivered', 'trial_started'], true)) {
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
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
            }

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

            $previousStatus = $transfer->status;

            $filePath = app(ManagedFileStorage::class)->storeFileContents(
                $signatureImage,
                'signatures',
                'png'
            );

            // تحديث طلب النقل بالتوقيع والحالة الجديدة
            $transfer->update([
                'signature' => $filePath,
                'status' => 'contract_signed',
                'contract_signed_at' => now(),
                'contract_signed_by' => auth('admin')->id()
            ]);

            \DB::commit();

            // إعادة تحميل النقل للتأكد من التحديث
            $transfer->refresh();
            $this->notifySponsorshipTransferStatusChanged($transfer, $previousStatus);

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ التوقيع بنجاح وتحديث حالة العقد',
                'signature_path' => $filePath,
                'signature_file' => basename($filePath),
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
            $this->assertCanAccessBranchModel($transfer);

            if ($this->transferBlockedByEscapeReport($transfer)) {
                return $this->escapeReportBlockedResponse();
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

            $filePath = app(ManagedFileStorage::class)->storeFileContents(
                $signatureImage,
                'signatures',
                'png'
            );

            // تحديث طلب النقل بتوقيع الشركة
            $transfer->update([
                'company_signature' => $filePath
            ]);

            // إعادة تحميل النقل للتأكد من التحديث
            $transfer->refresh();

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ توقيع الشركة بنجاح',
                'signature_path' => $filePath,
                'signature_file' => basename($filePath)
            ]);

        } catch (\Exception $e) {
            \Log::error('خطأ في حفظ توقيع الشركة: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حفظ توقيع الشركة: ' . $e->getMessage()
            ], 500);
        }
    }

    private function transferBlockedByEscapeReport(SponsorshipTransfer $transfer): bool
    {
        $transfer->loadMissing('contract.info');

        return (bool) $transfer->contract?->hasEscapeReport();
    }

    private function escapeReportBlockedResponse()
    {
        return response()->json([
            'success' => false,
            'message' => 'لا يمكن متابعة نقل الكفالة لوجود بلاغ هروب على العاملة',
        ], 422);
    }
}
