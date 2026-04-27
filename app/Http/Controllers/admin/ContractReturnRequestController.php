<?php

namespace App\Http\Controllers\admin;

use App\Models\ContractReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ContractReturnRequestController extends Controller
{
    public function index()
    {
        $returnRequests = ContractReturnRequest::with(["contract.maid", "user", "processedBy"])
            ->orderBy("created_at", "desc")
            ->paginate(15);

        return view("admin.contract-return-requests.index", compact("returnRequests"));
    }

    public function show($id)
    {
        $returnRequest = ContractReturnRequest::with(["contract.maid", "user", "processedBy"])
            ->findOrFail($id);

        return view("admin.contract-return-requests.show", compact("returnRequest"));
    }

    public function process(Request $request, $id)
    {
        $request->validate([
            "status" => "required|in:approved,rejected",
            "admin_notes" => "required|string|max:1000",
        ]);

        $returnRequest = ContractReturnRequest::findOrFail($id);

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

    public function statistics()
    {
        $stats = [
            "total" => ContractReturnRequest::count(),
            "pending" => ContractReturnRequest::where("status", "pending")->count(),
            "approved" => ContractReturnRequest::where("status", "approved")->count(),
            "rejected" => ContractReturnRequest::where("status", "rejected")->count(),
        ];

        return response()->json($stats);
    }
}
