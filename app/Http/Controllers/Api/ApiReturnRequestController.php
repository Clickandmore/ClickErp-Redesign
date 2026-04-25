<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Http\Resources\Api\RetuenRequestsResource;
use App\Models\Contract;
use App\Models\ContractReturnRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiReturnRequestController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sql = ContractReturnRequest::where('user_id', Auth::id())
            ->with(['contract.maid'])
            ->orderBy('created_at', 'desc');

        return generalReturn($request, $sql, RetuenRequestsResource::class, __("api.All items"));
    }

    /**
     * Show create data for return request.
     */
    public function create(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|integer',
        ]);

        $contract = Contract::where('id', $request->input('contract_id'))
            ->where('user_id', Auth::id())
            ->with(['maid'])
            ->first();

        if (!$contract) {
            return apiResponse(false, __('api.not found'), [], 404);
        }

        return apiResponse(true, 'تم بنجاح', [
            'contract' => $contract,
            'has_pending_return_request' => $contract->hasPendingReturnRequest(),
        ], 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'contract_id' => 'required|integer|exists:contracts,id',
            'notes' => 'required|string|max:1000',
        ]);

        $contract = Contract::where('id', $validated['contract_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$contract) {
            return apiResponse(false, __('api.not found'), [], 404);
        }

        if ($contract->hasPendingReturnRequest()) {
            return apiResponse(false, 'يوجد طلب استرجاع معلق لهذا العقد', [], 422);
        }

        $returnRequest = ContractReturnRequest::create([
            'contract_id' => $contract->id,
            'user_id' => Auth::id(),
            'notes' => $validated['notes'],
            'status' => 'pending',
        ])->load(['contract.maid']);

        return apiResponse(true, 'تم إرسال طلب الاسترجاع بنجاح', [
            'returnRequest' => $returnRequest,
        ], 200);
    }

    /**
     * Display the specified resource.
     */
    public function show($id)
    {
        $returnRequest = ContractReturnRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->with(['contract.maid', 'processedBy'])
            ->first();

        if (!$returnRequest) {
            return apiResponse(false, __('api.not found'), [], 404);
        }

        return apiResponse(true, 'تم بنجاح', [
            'returnRequest' => $returnRequest,
        ], 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit($id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        return apiResponse(false, 'Method Not Allowed', [], 405);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $returnRequest = ContractReturnRequest::where('id', $id)
            ->where('user_id', Auth::id())
            ->where('status', 'pending')
            ->first();

        if (!$returnRequest) {
            return apiResponse(false, 'لا يمكن إلغاء هذا الطلب', [], 404);
        }

        $returnRequest->delete();

        return apiResponse(true, 'تم إلغاء طلب الاسترجاع بنجاح', [], 200);
    }
}
