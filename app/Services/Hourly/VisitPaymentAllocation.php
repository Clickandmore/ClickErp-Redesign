<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyRentalOrder;

/** Explicit visit allocations for new collections; chronological allocation for legacy order payments. */
class VisitPaymentAllocation
{
    public function forOrder(HourlyRentalOrder $order): array
    {
        $order->loadMissing(['visits','payments']);
        $visits=$order->visits->sortBy(fn($v)=>($v->scheduled_start_at?->format('Y-m-d H:i:s')??'').' '.sprintf('%010d',$v->id));
        $result=[];$limits=[];
        foreach($visits as $v){$result[$v->id]=['paid'=>0,'methods'=>[],'payments'=>[]];$limits[$v->id]=(int)round((in_array($v->status,['cancelled','cancelled_by_client'])?$v->cancellation_fee:$v->visit_price)*100);}
        // Refunds reduce the available paid pool. Explicit allocations are capped at the current visit charge.
        $budget=max(0,(int)round($order->paid_amount*100));
        $payments=$order->payments->where('status','paid')->where('amount','>',0)->sortBy('id');
        $left=[];
        foreach($payments as $p)$left[$p->id]=(int)round($p->amount*100);
        $put=function($p,$id,$amount)use(&$result,&$limits,&$left,&$budget){
            if(!isset($result[$id]))return;
            $amount=max(0,min($amount,$limits[$id]-$result[$id]['paid'],$left[$p->id],$budget));
            if(!$amount)return;
            $result[$id]['paid']+=$amount;$result[$id]['payments'][$p->id]=($result[$id]['payments'][$p->id]??0)+$amount;
            $result[$id]['methods'][$p->method]=true;$left[$p->id]-=$amount;$budget-=$amount;
        };
        foreach($payments as $p)foreach(($p->provider_payload['visit_allocations']??[]) as $id=>$amount)$put($p,$id,(int)$amount);
        foreach($payments as $p)foreach($visits as $v)$put($p,$v->id,$left[$p->id]);
        foreach($result as &$row){$row['paid']/=100;$row['payments']=array_map(fn($amount)=>$amount/100,$row['payments']);$row['methods']=array_keys($row['methods']);}
        return $result;
    }
}
