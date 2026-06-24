<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ContractExtensionRequest;
use App\Models\ContractInfo;
use App\NewPermKeys;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ContractExtensionRequestController extends Controller
{
    /**
     * قائمة جميع طلبات التمديد (للمشرفين).
     */
    public function index(Request $request)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_VIEW,
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            abort(403);
        }

        $query = ContractExtensionRequest::with([
            'contract.user',
            'contract.maid',
            'requestedBy',
            'processedBy',
        ]);

        $this->applyRequestBranchFilter($query, $request, 'contract_extension_requests');

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $extensionRequests = $query->orderByDesc('created_at')->paginate(15)->appends($request->query());

        $stats = [
            'total'    => ContractExtensionRequest::count(),
            'pending'  => ContractExtensionRequest::pending()->count(),
            'approved' => ContractExtensionRequest::approved()->count(),
            'rejected' => ContractExtensionRequest::rejected()->count(),
        ];

        return view('admin.ingaz.ContractExtension.index', compact('extensionRequests', 'stats'));
    }

    /**
     * تقديم طلب تمديد عقد.
     */
    public function store(Request $request, $contractId)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_CREATE,
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لطلب تمديد العقد'], 403);
        }

        $contract = Contract::findOrFail($contractId);
        $this->assertCanAccessBranchModel($contract, 'contracts');

        $request->validate([
            'extension_years' => 'required|integer|min:1|max:10',
            'reason'          => 'nullable|string|max:1000',
        ], [
            'extension_years.required' => 'يرجى تحديد عدد سنوات التمديد',
            'extension_years.integer'  => 'عدد سنوات التمديد يجب أن يكون رقماً صحيحاً',
            'extension_years.min'      => 'الحد الأدنى للتمديد سنة واحدة',
            'extension_years.max'      => 'الحد الأقصى للتمديد 10 سنوات',
        ]);

        // منع تقديم طلب جديد إذا كان هناك طلب معلّق لنفس العقد
        $pendingExists = ContractExtensionRequest::where('contract_id', $contractId)
            ->where('status', 'pending')
            ->exists();

        if ($pendingExists) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد طلب تمديد قيد المعالجة لهذا العقد، يرجى الانتظار حتى يتم البت فيه',
            ], 422);
        }

        $extensionRequest = ContractExtensionRequest::create([
            'contract_id'    => $contract->id,
            'extension_years' => (int) $request->extension_years,
            'reason'         => $request->reason,
            'status'         => 'pending',
            'requested_by'   => Auth::guard('admin')->id(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم تقديم طلب التمديد بنجاح، في انتظار الموافقة',
            'data'    => $extensionRequest,
        ]);
    }

    /**
     * عرض تفاصيل طلب تمديد واحد.
     */
    public function show($id)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_VIEW,
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            abort(403);
        }

        $extensionRequest = ContractExtensionRequest::with([
            'contract.user',
            'contract.maid',
            'contract.info',
            'requestedBy',
            'processedBy',
            'notes.admin',
        ])->findOrFail($id);

        $this->assertCanAccessBranchModel($extensionRequest);

        return view('admin.ingaz.ContractExtension.show', compact('extensionRequest'));
    }

    /**
     * معالجة طلب التمديد: قبول أو رفض.
     * عند القبول: يُحدَّث contract_duration في ContractInfo وتُحسب ingaz_to الجديدة.
     */
    public function process(Request $request, $id)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية للبت في طلبات التمديد'], 403);
        }

        $request->validate([
            'status'      => 'required|in:approved,rejected',
            'admin_notes' => 'nullable|string|max:1000',
        ], [
            'status.required' => 'يرجى تحديد القرار (قبول أو رفض)',
            'status.in'       => 'القرار يجب أن يكون قبول أو رفض',
        ]);

        $extensionRequest = ContractExtensionRequest::with('contract.info')->findOrFail($id);
        $this->assertCanAccessBranchModel($extensionRequest);

        if ($extensionRequest->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'لا يمكن معالجة طلب تم البت فيه مسبقاً',
            ], 400);
        }

        DB::transaction(function () use ($extensionRequest, $request) {
            $extensionRequest->update([
                'status'       => $request->status,
                'admin_notes'  => $request->admin_notes,
                'processed_by' => Auth::guard('admin')->id(),
                'processed_at' => now(),
            ]);

            if ($request->status === 'approved') {
                $this->applyExtensionToContract($extensionRequest);
            }
        });

        $message = $request->status === 'approved'
            ? 'تم قبول طلب التمديد وتحديث مدة العقد بنجاح'
            : 'تم رفض طلب التمديد';

        return response()->json([
            'success' => true,
            'message' => $message,
            'data'    => $extensionRequest->fresh(),
        ]);
    }

    /**
     * إضافة ملاحظة على طلب التمديد.
     */
    public function storeNote(Request $request, $id)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_VIEW,
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لإضافة ملاحظات'], 403);
        }

        $request->validate([
            'note' => 'required|string|max:1000',
        ], [
            'note.required' => 'يرجى كتابة الملاحظة',
            'note.max'      => 'الملاحظة يجب ألا تتجاوز 1000 حرف',
        ]);

        $extensionRequest = ContractExtensionRequest::findOrFail($id);
        $this->assertCanAccessBranchModel($extensionRequest);

        $note = \App\Models\ContractExtensionRequestNote::create([
            'extension_request_id' => $extensionRequest->id,
            'admin_id'             => Auth::guard('admin')->id(),
            'note'                 => $request->note,
        ]);

        $note->load('admin');

        return response()->json([
            'success' => true,
            'message' => 'تمت إضافة الملاحظة بنجاح',
            'note'    => [
                'id'         => $note->id,
                'note'       => $note->note,
                'admin_name' => $note->admin?->name ?? 'غير محدد',
                'created_at' => $note->created_at->format('Y-m-d H:i'),
            ],
        ]);
    }

    /**
     * حذف ملاحظة من طلب التمديد.
     */
    public function deleteNote(Request $request, $id, $noteId)
    {
        if (!@AdminGuardHasAnyPerm([
            NewPermKeys::REC_CONTRACTS_EXTENSION_MANAGE,
            NewPermKeys::REC_CONTRACTS_MANAGE,
        ])) {
            return response()->json(['success' => false, 'message' => 'ليس لديك صلاحية لحذف الملاحظات'], 403);
        }

        $extensionRequest = ContractExtensionRequest::findOrFail($id);
        $this->assertCanAccessBranchModel($extensionRequest);

        $note = \App\Models\ContractExtensionRequestNote::where('extension_request_id', $extensionRequest->id)
            ->findOrFail($noteId);

        $note->delete();

        return response()->json(['success' => true, 'message' => 'تم حذف الملاحظة بنجاح']);
    }

    /**
     * تطبيق التمديد على بيانات العقد.
     */
    private function applyExtensionToContract(ContractExtensionRequest $extensionRequest): void
    {
        $contract = $extensionRequest->contract;
        $info = $contract->info;

        if (!$info) {
            $info = ContractInfo::create(['contract_id' => $contract->id]);
        }

        $addedYears = $extensionRequest->extension_years;
        $newDuration = ((int) ($info->contract_duration ?? 2)) + $addedYears;

        // تحديث مدة العقد
        $infoUpdates = ['contract_duration' => $newDuration];

        // تحديث تاريخ انتهاء الإنجاز إذا كان موجوداً
        if ($info->ingaz_to) {
            $infoUpdates['ingaz_to'] = Carbon::parse($info->ingaz_to)
                ->addYears($addedYears)
                ->toDateString();
        } elseif ($info->ingaz_from) {
            $infoUpdates['ingaz_to'] = Carbon::parse($info->ingaz_from)
                ->addYears($newDuration)
                ->toDateString();
        }

        $info->update($infoUpdates);
    }
}
