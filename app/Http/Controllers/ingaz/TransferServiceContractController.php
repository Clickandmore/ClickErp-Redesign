<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\SponsorshipTransfer;
use App\Models\TransferServiceContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class TransferServiceContractController extends Controller
{
    public $folderPath = "admin.ingaz.TransferServiceContract";
    public $mainRoute = "TransferServiceContract";

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = TransferServiceContract::with([
                'sponsorshipTransfer',
                'contract.maid.nationality',
                'maid',
                'oldSponsor',
                'newSponsor',
                'createdBy'
            ]);

            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            if ($request->filled('contract_number')) {
                $query->where('contract_number', 'like', '%' . $request->contract_number . '%');
            }

            if ($request->filled('date_from')) {
                $query->whereDate('created_at', '>=', $request->date_from);
            }

            if ($request->filled('date_to')) {
                $query->whereDate('created_at', '<=', $request->date_to);
            }

            if ($request->filled('request_type')) {
                $query->whereHas('maid', function ($q) use ($request) {
                    $q->ofRequestType($request->request_type);
                });
            }

            return DataTables::of($query)
                ->addIndexColumn()
                ->addColumn('contract_number', function($contract) {
                    return $contract->contract_number;
                })
                ->addColumn('transfer_info', function($contract) {
                    return 'طلب نقل #' . $contract->sponsorship_transfer_id;
                })
                ->addColumn('old_sponsor', function($contract) {
                    return $contract->oldSponsor ? $contract->oldSponsor->name : 'غير محدد';
                })
                ->addColumn('new_sponsor', function($contract) {
                    return $contract->newSponsor ? $contract->newSponsor->name : 'غير محدد';
                })
                ->addColumn('maid_name', function($contract) {
                    if (!$contract->maid) {
                        return 'غير محدد';
                    }

                    return '<div class="d-flex flex-column gap-1">'
                        . '<span>' . e($contract->maid->name) . '</span>'
                        . '<small class="text-muted">' . helperTrans('ingaz.request_type') . ': ' . e($contract->maid->request_type_label) . '</small>'
                        . '</div>';
                })
                ->addColumn('contract_value', function($contract) {
                    return number_format($contract->contract_value, 2) . ' ريال';
                })
                ->addColumn('status', function($contract) {
                    $badgeClass = '';
                    switch ($contract->status) {
                        case 'draft':
                            $badgeClass = 'bg-warning';
                            break;
                        case 'sent':
                            $badgeClass = 'bg-primary';
                            break;
                        case 'signed':
                            $badgeClass = 'bg-success';
                            break;
                        case 'active':
                            $badgeClass = 'bg-info';
                            break;
                        case 'completed':
                            $badgeClass = 'bg-success';
                            break;
                        case 'cancelled':
                            $badgeClass = 'bg-danger';
                            break;
                        default:
                            $badgeClass = 'bg-secondary';
                    }
                    return '<span class="badge ' . $badgeClass . '">' . $contract->status_text . '</span>';
                })
                ->addColumn('created_at', function($contract) {
                    return $contract->created_at->format('Y-m-d H:i');
                })
                ->addColumn('actions', function($contract) {
                    $actions = '<div class="btn-group" role="group">';

                    // عرض
                    $actions .= '<a href="' . route('TransferServiceContract.show', $contract->id) . '" class="btn btn-sm btn-info">
                                    <i class="fas fa-eye"></i> عرض
                                </a>';

                    // تحرير (إذا كان مسودة)
                    if ($contract->status === 'draft') {
                        $actions .= '<a href="' . route('TransferServiceContract.edit', $contract->id) . '" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i> تحرير
                                    </a>';
                    }

                    // طباعة العقد
                    $actions .= '<a href="' . route('print.transfer-service-contract', $contract->id) . '" target="_blank" class="btn btn-sm btn-success">
                                    <i class="fas fa-print"></i> طباعة
                                </a>';

                    $actions .= '</div>';

                    return $actions;
                })
                ->rawColumns(['maid_name', 'status', 'actions'])
                ->make(true);
        }

        return view($this->folderPath . '.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $transferId = $request->get('transfer_id');

        if (!$transferId) {
            return redirect()->route('SponsorshipTransfer.index')
                ->with('error', 'يجب تحديد طلب نقل الكفالة');
        }

        $transfer = SponsorshipTransfer::with([
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor'
        ])->findOrFail($transferId);

        // التحقق من أن الحالة تسمح بإنشاء عقد
        if (!$transfer->canCreateContract()) {
            return redirect()->route('SponsorshipTransfer.index')
                ->with('error', 'لا يمكن إنشاء عقد لهذا الطلب في الحالة الحالية');
        }

        // التحقق من عدم وجود عقد مسبقاً
        if ($transfer->transferServiceContract) {
            return redirect()->route('TransferServiceContract.show', $transfer->transferServiceContract->id)
                ->with('info', 'يوجد عقد نقل خدمات مُنشأ مسبقاً لهذا الطلب');
        }

        return view($this->folderPath . '.create', compact('transfer'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'sponsorship_transfer_id' => 'required|exists:sponsorship_transfers,id',
            'contract_value' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:1|max:365',
            'trial_start_date' => 'required|date|after_or_equal:today',
            'contract_start_date' => 'required|date|after_or_equal:today',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'special_conditions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            DB::beginTransaction();

            $transfer = SponsorshipTransfer::with(['contract.maid', 'oldSponsor', 'newSponsor'])
                ->findOrFail($request->sponsorship_transfer_id);

            // إنشاء عقد نقل الخدمات
            $contract = TransferServiceContract::create([
                'sponsorship_transfer_id' => $transfer->id,
                'contract_id' => $transfer->contract_id,
                'old_sponsor_id' => $transfer->old_sponsor_id,
                'new_sponsor_id' => $transfer->new_sponsor_id,
                'maid_id' => $transfer->contract->maid_id,
                'contract_value' => $request->contract_value,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->contract_value - $request->paid_amount,
                'trial_days' => $request->trial_days,
                'trial_start_date' => $request->trial_start_date,
                'contract_start_date' => $request->contract_start_date,
                'contract_end_date' => $request->contract_end_date,
                'special_conditions' => $request->special_conditions,
                'notes' => $request->notes,
                'status' => 'draft',
                'created_by' => auth('admin')->id()
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'تم إنشاء عقد نقل الخدمات بنجاح',
                'redirect' => route('TransferServiceContract.show', $contract->id)
            ]);

        } catch (\Exception $e) {
            DB::rollback();

            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في إنشاء العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(TransferServiceContract $transferServiceContract)
    {
        $transferServiceContract->load([
            'sponsorshipTransfer',
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor',
            'createdBy',
            'sentBy',
            'signedBy',
            'activatedBy',
            'completedBy',
            'cancelledBy'
        ]);

        return view($this->folderPath . '.show', compact('transferServiceContract'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(TransferServiceContract $transferServiceContract)
    {
        // يمكن تحرير العقد فقط إذا كان في حالة مسودة
        if ($transferServiceContract->status !== 'draft') {
            return redirect()->route('TransferServiceContract.show', $transferServiceContract->id)
                ->with('error', 'لا يمكن تحرير العقد في الحالة الحالية');
        }

        $transferServiceContract->load([
            'sponsorshipTransfer.contract.maid.nationality',
            'oldSponsor',
            'newSponsor'
        ]);

        return view($this->folderPath . '.edit', compact('transferServiceContract'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, TransferServiceContract $transferServiceContract)
    {
        // يمكن تحديث العقد فقط إذا كان في حالة مسودة
        if ($transferServiceContract->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن تحديث العقد في الحالة الحالية'
            ], 400);
        }

        $request->validate([
            'contract_value' => 'required|numeric|min:0',
            'paid_amount' => 'required|numeric|min:0',
            'trial_days' => 'required|integer|min:1|max:365',
            'trial_start_date' => 'required|date|after_or_equal:today',
            'contract_start_date' => 'required|date|after_or_equal:today',
            'contract_end_date' => 'nullable|date|after:contract_start_date',
            'special_conditions' => 'nullable|string',
            'notes' => 'nullable|string'
        ]);

        try {
            $transferServiceContract->update([
                'contract_value' => $request->contract_value,
                'paid_amount' => $request->paid_amount,
                'remaining_amount' => $request->contract_value - $request->paid_amount,
                'trial_days' => $request->trial_days,
                'trial_start_date' => $request->trial_start_date,
                'contract_start_date' => $request->contract_start_date,
                'contract_end_date' => $request->contract_end_date,
                'special_conditions' => $request->special_conditions,
                'notes' => $request->notes
            ]);

            return response()->json([
                'success' => true,
                'message' => 'تم تحديث عقد نقل الخدمات بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في تحديث العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(TransferServiceContract $transferServiceContract)
    {
        // يمكن حذف العقد فقط إذا كان في حالة مسودة
        if ($transferServiceContract->status !== 'draft') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن حذف العقد في الحالة الحالية'
            ], 400);
        }

        try {
            $transferServiceContract->delete();

            return response()->json([
                'success' => true,
                'message' => 'تم حذف عقد نقل الخدمات بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حذف العقد: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * طباعة عقد نقل الخدمات
     */
    public function print(TransferServiceContract $transferServiceContract)
    {
        $transferServiceContract->load([
            'sponsorshipTransfer',
            'contract.maid.nationality',
            'oldSponsor',
            'newSponsor'
        ]);

        $documentBranding = documentBranding();

        // إعدادات العقد الافتراضية
        $contractSettings = [
            'company_name' => $documentBranding['company_name'],
            'license_number' => '4001772',
            'license_date' => '2022/10/13',
            'commercial_register' => '1010601958',
            'commercial_date' => '1441/03/03',
            'address' => documentSetting('location', 'الرياض'),
            'phone' => documentSetting('phone', ''),
            'email' => documentSetting('email', ''),
            'fax' => '966550316770+',
            'logo' => documentSetting('document_logo'),
        ];

        return view('admin.ingaz.print.transfer-service-contract', compact('transferServiceContract', 'contractSettings'));
    }
}
