<?php
namespace App\Services\Hourly;
use App\Models\Hourly\HourlyRentalOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
class OrderSearch
{
    public function filters(Request $request): array {
        $data=$request->validate([
            'search'=>'nullable|string|max:150','customer_name'=>'nullable|string|max:150','order_number'=>'nullable|string|max:80',
            'from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d'.($request->filled('from')?'|after_or_equal:from':''),
            'status'=>'nullable|string|max:40','payment_status'=>'nullable|string|max:40','city_id'=>'nullable|integer','user_id'=>'nullable|integer',
            'admin_id'=>'nullable|integer','source'=>'nullable|string|max:30','hours'=>'nullable|numeric|min:0.5|max:24',
            'unique_id'=>'nullable|string|max:80','short_id'=>'nullable|integer|min:1','coupon_code'=>'nullable|string|max:100',
            'service_id'=>'nullable|integer','customer_source'=>'nullable|string|max:60',
            'installment'=>['nullable',Rule::in(['has','pending','overdue','none'])],
        ]);
        if($request->route('act')==='new')$data['status']='pending_confirmation';
        return $data;
    }
    public function query(array $f){
        $q=HourlyRentalOrder::query();
        foreach(['status','city_id','user_id'] as $key)if(isset($f[$key]) && $f[$key]!=='')$q->where($key,$f[$key]);
        if (!empty($f['source'])) {
            $f['source'] === 'customer' ? $q->where('source', '!=', 'admin') : $q->where('source', $f['source']);
        }
        if (!empty($f['payment_status'])) {
            if ($f['payment_status']==='cancelled') $q->where('status','cancelled');
            elseif ($f['payment_status']==='pending_approval') $q->where('status','pending_confirmation');
            else $q->where('payment_status',$f['payment_status']);
        }
        foreach(['admin_id'=>'created_by_admin_id','hours'=>'hours_per_visit','short_id'=>'id','service_id'=>'service_category_id','coupon_code'=>'coupon_code','unique_id'=>'booking_token'] as $key=>$column)if(isset($f[$key]) && $f[$key]!=='')$q->where($column,$f[$key]);
        if(!empty($f['customer_name']))$q->whereHas('user',fn($q)=>$q->where('name','like','%'.$f['customer_name'].'%'));
        if(!empty($f['customer_source']))$q->whereHas('user',fn($q)=>$q->where('register_from',$f['customer_source']));
        if(!empty($f['order_number']))$q->where(fn($q)=>$q->where('order_number','like','%'.$f['order_number'].'%')->orWhere('legacy_order_number','like','%'.$f['order_number'].'%'));
        if(!empty($f['from']))$q->whereDate('created_at','>=',$f['from']);
        if(!empty($f['to']))$q->whereDate('created_at','<=',$f['to']);
        if(!empty($f['search']))$q->where(fn($q)=>$q->where('order_number','like','%'.$f['search'].'%')->orWhere('legacy_order_number','like','%'.$f['search'].'%')->orWhere('invoice_number','like','%'.$f['search'].'%')->orWhereHas('user',fn($q)=>$q->where('name','like','%'.$f['search'].'%')->orWhere('phone','like','%'.$f['search'].'%')));
        if(!empty($f['installment'])){
            $method=$f['installment']==='none'?'whereNotExists':'whereExists';
            $q->$method(function($q)use($f){$q->selectRaw('1')->from('hourly_order_installments as i')->whereColumn('i.hourly_rental_order_id','hourly_rental_orders.id');
                if(in_array($f['installment'],['pending','overdue']))$q->whereColumn('i.paid_amount','<','i.amount');
                if($f['installment']==='overdue')$q->where('i.due_date','<',now()->toDateString());
            });
        }
        return $q;
    }
    public const COLUMNS=['order_number'=>'رقم الطلب','created_at'=>'تاريخ الإنشاء','customer'=>'العميل','balance'=>'رصيد العميل','city'=>'المدينة','district'=>'الحي','service'=>'الخدمة','product'=>'المنتج','starts_at'=>'بدء الخدمة','hours_per_visit'=>'مدة الزيارة','workers_per_visit'=>'الموظفون','initial_amount'=>'المبلغ الأولي','total_amount'=>'الإجمالي','tax_amount'=>'الضريبة','paid_amount'=>'المحصل','status'=>'الحالة','payment_status'=>'حالة السداد','payment_method'=>'طريقة الدفع','source'=>'المصدر','coupon_code'=>'الكوبون','booking_token'=>'المعرف الفريد','id'=>'المعرف القصير'];
    public function cell($order,string $key){return match($key){'customer'=>$order->user?->name,'balance'=>$order->user?->hourly_wallet_balance,'city'=>$order->city?->name_ar,'service'=>$order->serviceCategory?->name,'product'=>$order->package_snapshot['title']??'',default=>$order->$key};}
}
