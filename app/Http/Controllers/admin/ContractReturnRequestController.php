<?php

namespace App\Http\Controllers\admin;

use App\Models\ContractReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractReturnRequestController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractReturnRequest::with(["contract.maid", "user", "processedBy"]);
        $this->applyRequestBranchFilter($query, $request, 'contract_return_requests');

        $returnRequests = $query
            ->orderBy("created_at", "desc")
            ->paginate(15)
            ->appends($request->query());

        return view("admin.contract-return-requests.index", compact("returnRequests"));
    }

    public function show($id)
    {
        $returnRequest = ContractReturnRequest::with(["contract.maid", "user", "processedBy"])
            ->findOrFail($id);
        $this->assertCanAccessBranchModel($returnRequest);

        return view("admin.contract-return-requests.show", compact("returnRequest"));
    }

    public function process(Request $request, $id)
    {
        $request->validate([
            "status" => "required|in:approved,rejected",
            "admin_notes" => "required|string|max:1000",
        ]);

        $returnRequest = ContractReturnRequest::findOrFail($id);
        $this->assertCanAccessBranchModel($returnRequest);

        if ($returnRequest->status !== "pending") {
            return response()->json([
                "success" => false,
                "message" => "لا يمكن معالجة طلب تم معالجته مسبقاً"
            ], 400);
        }

        $returnRequest->update([
            "status" => $request->status,
            "admin_notes" => $request->admin_notes,
            "processed_by" => Auth::id(),
            "processed_at" => now()
        ]);

        return response()->json([
            "success" => true,
            "message" => "تم معالجة طلب الاسترجاع بنجاح",
            "data" => $returnRequest
        ]);
    }

    public function statistics(Request $request)
    {
        $query = $this->applyRequestBranchFilter(ContractReturnRequest::query(), $request, 'contract_return_requests');

        $stats = [
            "total" => (clone $query)->count(),
            "pending" => (clone $query)->where("status", "pending")->count(),
            "approved" => (clone $query)->where("status", "approved")->count(),
            "rejected" => (clone $query)->where("status", "rejected")->count(),
        ];

        return response()->json($stats);
    }
}
