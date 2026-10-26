<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder,HourlyPackage};
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;

class DuplicateBookingService
{
    private function normalized($value): string {return preg_replace('/\s+/u',' ',mb_strtolower(trim((string)$value)));}
    private function sameAddress(HourlyRentalOrder $order,array $address): bool
    {
        if((int)$order->city_id!==(int)($address['city_id']??0))return false;
        foreach(['building','unit'] as $key)if($this->normalized($order->$key)!==$this->normalized($address[$key]??''))return false;
        $text=$this->normalized($address['address']??$address['address_text']??'');
        if($text!=='' && $text===$this->normalized($order->address_text))return true;
        return $order->latitude!==null && $order->longitude!==null && isset($address['latitude'],$address['longitude'])
            && abs((float)$order->latitude-(float)$address['latitude'])<0.00005
            && abs((float)$order->longitude-(float)$address['longitude'])<0.00005;
    }
    public function assertAvailable(int $user,array $address,HourlyPackage $item,array $slots,?int $exceptVisit=null,bool $admin=false): void
    {
        $matches=$this->matches($user,$address,$item,$slots,$exceptVisit,$admin);
        if($matches)throw new HttpResponseException(response()->json(['code'=>'duplicate_visit',
            'message'=>'يوجد حجز قائم لنفس العميل والخدمة والعنوان في هذا الموعد. هل تريد إضافة عاملة إلى الزيارة الحالية بدل إنشاء طلب جديد؟',
            'duplicate'=>['visits'=>$matches]],409));
    }
    public function matches(int $user,array $address,HourlyPackage $item,array $slots,?int $exceptVisit=null,bool $admin=false): array
    {
        if(!$slots)return [];
        $starts=array_map(fn($s)=>Carbon::parse($s['starts_at']),$slots);$ends=array_map(fn($s)=>Carbon::parse($s['ends_at']),$slots);
        $query=HourlyRentalOrder::where('user_id',$user)->whereIn('status',['active','pending_confirmation'])
            ->where(fn($q)=>$q->where('status','active')->orWhereNull('expires_at')->orWhere('expires_at','>',now()));
        if($item->service_category_id)$query->where('service_category_id',$item->service_category_id);
        else $query->where('hourly_package_id',$item->id);
        $constrain=function($q)use($starts,$ends,$exceptVisit){
            $q->whereNotIn('status',['cancelled','cancelled_by_client','expired','completed','no_show'])
              ->where('scheduled_start_at','<',max($ends))->where('scheduled_end_at','>',min($starts));
            if($exceptVisit)$q->where('id','!=',$exceptVisit);
        };
        $orders=$query->whereHas('visits',$constrain)->with(['visits'=>$constrain])->get();
        $matches=[];
        foreach($orders as $order){
            if(!$this->sameAddress($order,$address))continue;
            foreach($order->visits as $visit){
                $clash=false;
                foreach($slots as $slot)if($visit->scheduled_start_at->lt(Carbon::parse($slot['ends_at'])) && $visit->scheduled_end_at->gt(Carbon::parse($slot['starts_at']))){$clash=true;break;}
                if(!$clash)continue;
                $canAdd=$visit->status==='scheduled' && $visit->scheduled_start_at->isFuture() && $visit->workers_count<20;
                $url=$admin?route('admin.hourly-orders.show',$order):route('web.hourly.order',$order->id);
                $matches[]=['order_id'=>$order->id,'order_number'=>$order->order_number,'visit_id'=>$visit->id,
                    'visit_number'=>$visit->visit_number,'starts_at'=>$visit->scheduled_start_at->format('Y-m-d H:i'),
                    'ends_at'=>$visit->scheduled_end_at->format('Y-m-d H:i'),'workers_count'=>(int)$visit->workers_count,
                    'can_add_worker'=>$canAdd,'order_url'=>$url,'add_worker_url'=>$canAdd?$url.'?add_worker_visit='.$visit->id:null];
            }
        }
        return $matches;
    }
}
