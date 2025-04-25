<?php

namespace App\Http\Controllers\ingaz;

use App\Http\Controllers\Controller;
use App\Models\CancelContractRequests;
use Illuminate\Http\Request;

class ContractsController extends Controller
{
    public function cancel_contracts(Request $request){
        $data = CancelContractRequests::orderBy('created_at', 'desc')->get();
        return view('admin.ingaz.cancel_contracts.index', compact('data'));
    }

}
