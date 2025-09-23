<?php

namespace App\Http\Controllers\site\profile;

use App\Http\Actions\ingaz\OrderAction;
use App\Http\Controllers\Controller;
use App\Models\ingaz\RecruitmentRequest;
use App\Models\ingaz\ReturnRequest;
use App\Models\TravelHistory;
use Illuminate\Support\Facades\Auth;
class OrderController extends Controller
{
    public function index(OrderAction $orderAction)
    {
        // $orders = RecruitmentRequest::where('user_id', auth()->user()->id)->get();
        // return view('site.pages.profile.last_orders', get_defined_vars());

        $current = $orderAction->getIndex('current');
        $previous = $orderAction->getIndex('previous') ;
        $order_progressbar = ['refused'=> 0,'new'=>20,'paid'=>40,'completed'=>60,'started'=>80,'done'=>100];
        return view('site.pages.profile.last_orders_new', get_defined_vars());
    }

    public function show(OrderAction $orderAction,$id)
    {
        $data['order'] = $orderAction->find($id);
        if($data['order']->user_id != Auth::user()->id){
            abort(404);
        }
        $data['order_progressbar'] = ['refused'=> 0,'new'=>20,'paid'=>40,'completed'=>60,'started'=>80,'done'=>100];
        $data['returnRequest'] = ReturnRequest::where('order_id',$id)->first();
        $data['travelRequest'] = TravelHistory::where('order_id',$id)->first();
        return view('site.pages.profile.last_orders_show',$data);
    }


}
