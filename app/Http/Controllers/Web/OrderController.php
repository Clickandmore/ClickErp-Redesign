<?php

namespace App\Http\Controllers\Web;

use App\Http\Actions\ingaz\OrderAction as objAction;
use App\Http\Controllers\Controller;
// use App\Http\Requests\ingaz\OrderRequest;
use App\Models\ingaz\Order;
use Illuminate\Http\Request;
use App\Http\Resources\Api\OrderResource as objResource;
use Illuminate\Support\Facades\Auth;
use App\Models\ingaz\OrderTransfers;
use App\Models\ingaz\ReturnRequest;
use App\Models\TravelHistory;
use Illuminate\Support\Facades\Validator;

class OrderController extends Controller
{
    public $postData = ['maid_id','job_type_id','payment_method','payment_type'];

    public function index(objAction $orderAction,Request $request)
    {
        $data['current'] = $orderAction->getIndex('current');
        $data['previous'] = $orderAction->getIndex('previous') ;
        $data['order_progressbar'] = ['refused'=> 0,'new'=>20,'paid'=>40,'completed'=>60,'started'=>80,'done'=>100];

        return view('web.ingaz.orders.index',$data);
    }
    public function show(objAction $orderAction,$id)
    {
        $data['order'] = $orderAction->find($id);
        if($data['order']->user_id != Auth::user()->id){
            abort(404);
        }
        $data['order_progressbar'] = ['refused'=> 0,'new'=>20,'paid'=>40,'completed'=>60,'started'=>80,'done'=>100];
        $data['returnRequest'] = ReturnRequest::where('order_id',$id)->first();
        $data['travelRequest'] = TravelHistory::where('order_id',$id)->first();
        return view('web.ingaz.orders.show',$data);
    }
    public function store(Request $request, objAction $action)
    {
        // Check if user has more than 2 active orders
        $activeOrdersCount = Order::where('user_id', Auth::user()->id)
            ->whereNotIn('status', ['done', 'refused','canceled','completed'])
            ->count();

        if ($activeOrdersCount >= 3) {
            return jsonSuccess(null, ' يوجد اكثر من ثلاث طلبات مفعلة ', 410);
        }

        // Check if maid is already booked
        $order = Order::where('maid_id', $request->maid_id)->whereNotIn('status', ['done', 'refused'])->first();
        if ($order){
            return jsonSuccess(null, 'هذه العامله محجوزة', 410);
        }

        $dataInsert = $request->only($this->postData);
        $data = $action->storeOrder($dataInsert, $request);
        $orderID = $data->id; // Capture the created order ID

        // Update maid availability status
        \App\Models\ingaz\Maid::where('id', $request->maid_id)->update([
            'status' => \App\Models\ingaz\Maid::STATUS_RESERVED['en'],
            'available' => 0,
            'updated_at' => now()
        ]);

        if ($request->payment_method == "online") {
            $data = [
                "order" => objResource::make($data),
                "customer_name" => @$data->details->admin->name,
                "redirectUrl" => "#",
                'paymentSuccess' => "#",
                'paymentaFiled' => "#",
                "order_id" => $orderID // Include the order ID in the response
            ];
        } else {
            $data = [
                "order" => objResource::make($data),
                "customer_name" => @$data->details->admin->name,
                "redirectUrl" => "#",
                'paymentSuccess' => "#",
                'paymentaFiled' => "#",
                "order_id" => $orderID // Include the order ID in the response
            ];
        }

        return jsonSuccess($data);
    }


    public function submitBankTransfer(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_id' => 'required',
            'file' => 'required|file|mimes:jpeg,png,jpg,pdf|max:2048',
            'notes' => 'nullable|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'code' => 422,
                'message' => 'Validation Error',
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $originalFileName = $file->getClientOriginalName();
            $filePath = $file->storeAs('order_transfers', $originalFileName, 'public');
        }

        $orderTransfer = new OrderTransfers();
        $orderTransfer->order_id = $request->order_id;
        $orderTransfer->file = $filePath;
        $orderTransfer->notes = $request->notes;
        $orderTransfer->transfer_type = $request->transfer_type;
        $orderTransfer->save();

        return response()->json([
            'code' => 200,
            'message' => 'Bank transfer details submitted successfully',
            'data' => $orderTransfer
        ]);
    }




    public function savePendingOrder(Request $request)
    {
        try {
            $orderData = $request->input('order_data');

            \Log::info('Save Pending Order Request:', $orderData);

            // حفظ البيانات في الجلسة
            session(['pending_order_data' => $orderData]);

            \Log::info('Session saved successfully');

            return response()->json([
                'success' => true,
                'message' => 'تم حفظ بيانات الطلب بنجاح'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ في حفظ بيانات الطلب'
            ], 500);
        }
    }}
