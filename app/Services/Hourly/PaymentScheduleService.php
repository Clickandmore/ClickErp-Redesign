<?php
namespace App\Services\Hourly;

use App\Models\Hourly\{HourlyRentalOrder,HourlyVisit};
use Carbon\Carbon;
use Illuminate\Support\Facades\{DB,Schema};

class PaymentScheduleService
{
    public const LABELS=['prepaid'=>'مقدمًا بالكامل','per_visit'=>'لكل زيارة','installments'=>'أقساط بمواعيد محددة','legacy'=>'حسب شروط الطلب السابقة'];
    private function cents($value): int { return max(0,(int)round((float)$value*100)); }

    public function policy(HourlyRentalOrder $order): string
    {
        $policy=$order->package_snapshot['payment_policy']??null;
        if(isset(self::LABELS[$policy??'']))return $policy;
        // Existing customer credit terms remain authoritative for historical orders.
        if(!empty($order->booking_metadata['payment_term']))return $order->booking_metadata['payment_term']==='prepaid'?'prepaid':'legacy';
        return ($order->package_snapshot['price_display']??'total')==='per_visit'?'per_visit':'legacy';
    }

    public function forOrder(HourlyRentalOrder $order): array
    {
        $policy=$this->policy($order);$order->loadMissing(['visits','payments']);
        $allocated=app(VisitPaymentAllocation::class)->forOrder($order);
        $remaining=max(0,$this->cents($order->total_amount)-$this->cents($order->paid_amount));
        $first=$order->visits->sortBy('visit_number')->first(fn($v)=>!in_array($v->status,['cancelled','cancelled_by_client','expired'],true));
        $visits=[];$due=0;$overdue=0;
        foreach($order->visits as $visit){
            $cancelled=in_array($visit->status,['cancelled','cancelled_by_client'],true);
            $charge=$visit->status==='expired'?0:$this->cents($cancelled?$visit->cancellation_fee:$visit->visit_price);
            $paid=min($charge,$this->cents($allocated[$visit->id]['paid']??0));$left=max(0,$charge-$paid);
            $started=in_array($visit->status,['en_route','delivered','in_progress','worker_finished','picked_up','completed','no_show'],true);
            $matured=$policy==='prepaid' || $started || ($cancelled && $charge>0) || ($visit->scheduled_start_at && $visit->scheduled_start_at->lte(now()))
                || ($order->status==='pending_confirmation' && $first?->id===$visit->id);
            $late=$policy!=='installments' && $left>0 && $matured && ($visit->status==='completed' || ($visit->scheduled_end_at && $visit->scheduled_end_at->lt(now())));
            $visits[$visit->id]=['policy'=>$policy,'charge'=>$charge/100.0,'paid'=>$paid/100.0,'remaining'=>$left/100.0,
                'payment_status'=>$left===0?'paid':($paid>0?'partial':'unpaid'),
                'is_due'=>$policy!=='installments' && $matured && $left>0,'due_now'=>$policy!=='installments' && $matured?$left/100.0:0,
                'is_overdue'=>$late,'due_at'=>$visit->scheduled_start_at?->toIso8601String()];
            if($matured)$due+=$left;if($late)$overdue+=$left;
        }
        $installments=[];
        if($policy==='installments'){
            $budget=$this->cents($order->total_amount);$paidBudget=$this->cents($order->paid_amount);$due=0;$overdue=0;
            if(Schema::hasTable('hourly_order_installments')){
                foreach(DB::table('hourly_order_installments')->where('hourly_rental_order_id',$order->id)->orderBy('due_date')->orderBy('id')->get() as $row){
                    $amount=min($budget,$this->cents($row->amount));$budget-=$amount;
                    $paid=min($amount,$paidBudget);$paidBudget-=$paid;$left=$amount-$paid;
                    $matured=Carbon::parse($row->due_date)->startOfDay()->lte(today());
                    $late=$matured && Carbon::parse($row->due_date)->lt(today()) && $left>0;
                    $installments[]=['id'=>(int)$row->id,'due_date'=>$row->due_date,'amount'=>$amount/100.0,'paid'=>$paid/100.0,'remaining'=>$left/100.0,
                        'payment_status'=>$left===0?'paid':($paid>0?'partial':'unpaid'),'is_due'=>$matured && $left>0,'is_overdue'=>$late];
                    if($matured)$due+=$left;if($late)$overdue+=$left;
                }
            }
        } elseif($policy==='prepaid'){$due=$remaining;}
        elseif($policy==='legacy'){
            $charge=$this->cents($order->package_snapshot['charge_now']??$order->total_amount);$paid=$this->cents($order->paid_amount);
            $due=$paid<$charge?$charge-$paid:$remaining;
        }
        if(in_array($order->status,['cancelled','expired'],true))$due=0;
        // Every visit is finished, so the whole balance (including installments not yet reached) is due.
        if($order->status===HourlyOrderService::AWAITING_PAYMENT)$due=$remaining;
        return ['policy'=>$policy,'policy_label'=>self::LABELS[$policy],'remaining'=>$remaining/100.0,
            'due_now'=>min($remaining,max(0,$due))/100.0,'overdue'=>min($remaining,$overdue)/100.0,'visits'=>$visits,'installments'=>$installments];
    }

    public function collectible(HourlyRentalOrder $order,HourlyVisit $visit): float
    {
        $schedule=$this->forOrder($order);
        if($schedule['policy']==='per_visit')return min($schedule['due_now'],$schedule['visits'][$visit->id]['due_now']??0);
        return $schedule['policy']==='legacy'?$schedule['remaining']:$schedule['due_now'];
    }

    /** Allocations for a real payment, in cents; never fabricate payments on service completion. */
    public function allocations(HourlyRentalOrder $order,int $amount,?int $visit=null): array
    {
        if($this->policy($order)!=='per_visit')return [];
        $schedule=$this->forOrder($order);$result=[];
        $rows=$order->visits->sortBy(fn($v)=>($v->scheduled_start_at?->format('Y-m-d H:i:s')??'9999').' '.sprintf('%010d',$v->id));
        foreach($rows as $row){
            if($visit!==null && $row->id!==$visit)continue;
            $part=min($amount,$this->cents($schedule['visits'][$row->id]['due_now']??0));
            if($part){$result[$row->id]=$part;$amount-=$part;}
        }
        return $result;
    }

    public function generateInstallments(HourlyRentalOrder $order,bool $replace=false): void
    {
        if($this->policy($order)!=='installments')return;
        if(!Schema::hasTable('hourly_order_installments'))HourlyOrderService::reject('يجب تهيئة جدول الأقساط أولًا.');
        $query=DB::table('hourly_order_installments')->where('hourly_rental_order_id',$order->id);
        if($query->exists()){if(!$replace)return;$query->delete();}
        $left=$this->cents($order->total_amount);$deposit=min($left,$this->cents($order->package_snapshot['deposit_amount']??0));
        $monthly=$this->cents($order->package_snapshot['monthly_amount']??0);
        if($left>$deposit && ($monthly<=0 || (int)ceil(($left-$deposit)/$monthly)>120))HourlyOrderService::reject('حدد قسطًا شهريًا موجبًا بحيث لا يتجاوز الجدول 120 قسطًا.');
        $rows=[];$base=Carbon::parse($order->starts_at??now())->startOfDay();
        if($deposit){$rows[]=['due_date'=>today()->toDateString(),'amount'=>$deposit/100.0];$left-=$deposit;}
        for($i=$deposit?1:0;$left>0;$i++){
            $amount=min($monthly,$left);$left-=$amount;
            $rows[]=['due_date'=>$base->copy()->addMonthsNoOverflow($i)->toDateString(),'amount'=>$amount/100.0];
        }
        foreach($rows as $row)DB::table('hourly_order_installments')->insert($row+['hourly_rental_order_id'=>$order->id,'paid_amount'=>0,'created_at'=>now(),'updated_at'=>now()]);
    }

    public function syncInstallments(HourlyRentalOrder $order): void
    {
        if($this->policy($order)!=='installments')return;
        foreach($this->forOrder($order)['installments'] as $row)DB::table('hourly_order_installments')->where('id',$row['id'])->update(['paid_amount'=>$row['paid'],'updated_at'=>now()]);
    }
}