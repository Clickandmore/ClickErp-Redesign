<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\admin\Controller;
use App\Models\CancelContractRequests;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ApiContractController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        // Fetch contracts for the authenticated user, ordered by creation date
        $contracts = Contract::with(['maid', 'info'])
            ->withCount(['returnRequests as pending_return_requests_count' => function ($query) {
                $query->where('status', 'pending');
            }])
            ->where('user_id', Auth::id())
            ->orderBy('created_at', 'desc')
            ->get();

        $current = [];
        $previous = [];

        foreach ($contracts as $contract) {
            $contract->has_pending_return_request = $contract->pending_return_requests_count > 0;

            // Check if the contract status is one of the "previous" statuses
            if (in_array($contract->status, ['arrival', 'closed', 'shelter', 'under_warranty'])) {
                $previous[] = $contract;
            } else {
                $current[] = $contract;
            }
        }

        $result = [
            "contracts" => $contracts,
            "current" => $current,
            "previous" => $previous,
        ];

        return apiResponse(true, __('api.All items'), $result, 200);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $data = Contract::with(['maid', 'user'])->where('user_id', Auth::user()->id)
            ->where('id', $id)
            ->first();

        if (!$data)
            return apiResponse(false, 'هذا العقد غير موجود', [], 404);

        $info = getContractInfo($id);
        $cancelExists = CancelContractRequests::where('user_id', Auth::user()->id)->where('contract_id', $id)->exists();

        $result = [
            'contract' => $data,
            'info' => $info,
            'cancelExists' => $cancelExists,
        ];

        return apiResponse(true, __('api.All items'), $result, 200);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function cancel(Request $request)
    {
        $request->validate([
            'contract_id' => 'required|exists:contracts,id',
            'notes' => 'required|string',
        ]);

        $existingRequest = CancelContractRequests::where('user_id', Auth::id())
            ->where('contract_id', $request->contract_id)
            ->first();

        if ($existingRequest) {
            return response()->json([
                'success' => false,
                'message' => __('api.cancellation_already_exists')
            ]);
        }

        CancelContractRequests::create([
            'user_id' => Auth::id(),
            'contract_id' => $request->contract_id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => __('api.cancellation_submitted_successfully')
        ]);
    }
}
