<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Controllers\admin\Controller;
use App\Models\Contract;
use App\Models\ContractReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractReturnRequestController extends Controller
{
    public function index()
    {
        $user = Auth::guard('web')->user();
        $returnRequests = ContractReturnRequest::where('user_id', $user->id)
            ->with(['contract.maid'])
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('site.pages.profile.contract_return_requests', compact('returnRequests'));
    }

    public function create($contractId)
    {
        $contract = Contract::where('id', $contractId)
            ->where('user_id', Auth::guard('web')->id())
            ->with(['maid'])
            ->firstOrFail();

        // التحقق من عدم وجود طلب استرجاع معلق
        if ($contract->hasPendingReturnRequest()) {
            return redirect()->back()->with('error', 'يوجد طلب استرجاع معلق لهذا العقد');
        }

        return view('web.ingaz.contract-return-requests.create', compact('contract'));
    }

    public function store(Request $request, $contractId)
    {
        $request->validate([
            'notes' => 'required|string|max:1000',
        ]);

        $contract = Contract::where('id', $contractId)
            ->where('user_id', Auth::guard('web')->id())
            ->firstOrFail();

        // التحقق من عدم وجود طلب استرجاع معلق
        if ($contract->hasPendingReturnRequest()) {
            return response()->json([
                'success' => false,
                'message' => 'يوجد طلب استرجاع معلق لهذا العقد'
            ], 400);
        }

        $returnRequest = ContractReturnRequest::create([
            'contract_id' => $contract->id,
            'user_id' => Auth::guard('web')->id(),
            'notes' => $request->notes,
            'status' => 'pending'
        ]);

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال طلب الاسترجاع بنجاح',
            'data' => $returnRequest
        ]);
    }

    public function show($id)
    {
        $returnRequest = ContractReturnRequest::where('id', $id)
            ->where('user_id', Auth::guard('web')->id())
            ->with(['contract.maid', 'processedBy'])
            ->firstOrFail();

        return view('web.ingaz.contract-return-requests.show', compact('returnRequest'));
    }

    public function cancel($id)
    {
        $returnRequest = ContractReturnRequest::where('id', $id)
            ->where('user_id', Auth::guard('web')->id())
            ->where('status', 'pending')
            ->firstOrFail();

        $returnRequest->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم إلغاء طلب الاسترجاع بنجاح'
        ]);
    }
}
