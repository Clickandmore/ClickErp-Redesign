<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyRentalOrder;
use App\Services\Hourly\CustomerAdditionalWorker;
use Illuminate\Http\Request;

class AdditionalWorkerController extends Controller
{
    private function owned(int $id,int $visit): array {
        $order=HourlyRentalOrder::whereKey($id)->where('user_id',auth('api')->id())->firstOrFail();
        return [$order,$order->visits()->whereKey($visit)->firstOrFail()];
    }
    public function preview(int $id,int $visit,CustomerAdditionalWorker $service){
        [$order,$row]=$this->owned($id,$visit);
        return response()->json(['data'=>$service->preview($order,$row)]);
    }
    public function store(Request $request,int $id,int $visit,CustomerAdditionalWorker $service){
        [$order]=$this->owned($id,$visit);
        $data=$request->validate(['request_token'=>'required|uuid','confirmed'=>'required|accepted','worker_id'=>'nullable|integer',
            'expected_workers'=>'required|integer|between:1,19','expected_price'=>'required|numeric|min:0',
            'expected_total'=>'required|numeric|min:0']);
        return response()->json(['data'=>$service->add($order,$visit,$data,(int)auth('api')->id()),
            'redirect'=>route('web.hourly.order',$order->id),'message'=>'تمت إضافة عاملة للزيارة الحالية وتحديث السعر دون إنشاء طلب جديد.']);
    }
}