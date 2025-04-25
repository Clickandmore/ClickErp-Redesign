<?php

namespace App\Http\Controllers\Web;

use Carbon\Carbon;
use App\Models\Contract;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Models\CancelContractRequests;
use Illuminate\Support\Facades\Session;

class ContractsController extends Controller
{
    public function index(Request $request){

        // Fetch contracts for the authenticated user, ordered by creation date
        $data = Contract::where('user_id', Auth::user()->id)->orderBy('created_at', 'desc')->get();

        $current = [];
        $previous = [];

        foreach ($data as $contract){
            // Assuming getContractInfo() fetches additional contract information
            $info = getContractInfo($contract->id);
            if(!empty($info->contract_date)){
                $contractDate = Carbon::parse($info->contract_date);
                $contractDurationInDays = $info->contract_duration;

                // Calculate the end date of the contract
                $contractEndDate = $contractDate->copy()->addDays($contractDurationInDays);

                // Determine if the contract is still active
                if (Carbon::now()->lessThanOrEqualTo($contractEndDate)) {
                    $current[] = $contract;
                } else {
                    $previous[] = $contract;
                }
            }

        }

        return view('web.ingaz.contracts.index', compact('data', 'current', 'previous'));
    }

    public function show($id){
        $data = Contract::where('user_id', Auth::user()->id)->first();
        if(!$data)
            return  redirect()->back();
        $info = getContractInfo($id);
        $cancelExists = CancelContractRequests::where('user_id', Auth::user()->id)->where('contract_id', $id)->exists();
        return view('web.ingaz.contracts.show', compact('data','info','cancelExists'));
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
                'message' => 'A cancellation request for this contract already exists.'
            ]);
        }

        CancelContractRequests::create([
            'user_id' => Auth::id(),
            'contract_id' => $request->contract_id,
            'notes' => $request->notes,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contract cancellation request submitted successfully.'
        ]);
    }


}
