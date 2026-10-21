<?php
namespace App\Services\Hourly;

use App\Models\Hourly\HourlyVisit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class VisitSearch
{
    public const MULTI=['status','start_time','admin_id','team_id','leader_id','worker_id','driver_id','city_id','service_id','customer_source'];
    public const CANCELLED=['cancelled','cancelled_by_client','expired'];
    public const CLOSED=['completed','cancelled','cancelled_by_client','expired','no_show'];
    public const SORTS=['date'=>'تاريخ الزيارة','customer'=>'اسم العميل','address'=>'العنوان','price'=>'سعر الزيارة','duration'=>'المدة','workers'=>'الموظفون','leader'=>'قائد الفريق','driver'=>'السائق','status'=>'حالة العملية'];
    public const STATUSES=['pending_confirmation'=>'في انتظار التأكيد','unscheduled'=>'بلا موعد','unassigned'=>'غير مخصص','assigned'=>'تعيين مكتمل','partial'=>'تعيين جزئي','missed'=>'فات موعدها — لم تُنفّذ','scheduled'=>'مجدولة','en_route'=>'في الطريق','delivered'=>'تم التوصيل','in_progress'=>'بدأ الخدمة','worker_finished'=>'أنهت العاملة','picked_up'=>'تم الاستلام','completed'=>'مكتملة','cancelled'=>'ملغاة','cancelled_by_client'=>'ألغاها العميل','expired'=>'منتهية الصلاحية','no_show'=>'لم يحضر العميل'];

    public function filters(Request $request,string $mode): array
    {
        foreach(self::MULTI as $key)if($request->has($key) && !is_array($request->input($key)))$request->merge([$key=>$request->filled($key)?[$request->input($key)]:[]]);
        $rules=[
            'search'=>'nullable|string|max:150','from'=>'nullable|date_format:Y-m-d','to'=>'nullable|date_format:Y-m-d'.($request->filled('from')?'|after_or_equal:from':''),
            'day_part'=>'nullable|in:am,pm','status.*'=>[Rule::in(array_keys(self::STATUSES))], 'start_time.*'=>'date_format:H:i',
            'payment_status'=>'nullable|in:paid,unpaid,partial','payment_method'=>'nullable|string|max:30',
            'installment'=>'nullable|in:has,none,pending,overdue','tax_rate'=>'nullable|numeric|between:0,100',
            'attribute_id'=>'nullable|integer','customer_source.*'=>'string|max:100','show_cancelled'=>'nullable|boolean',
            'sort'=>['nullable',Rule::in(array_keys(self::SORTS))],'direction'=>'nullable|in:asc,desc','per_page'=>'nullable|in:10,25,50,100',
        ];
        foreach(self::MULTI as $key){$rules[$key]='nullable|array|max:100';if(str_ends_with($key,'_id'))$rules[$key.'.*']=['regex:/^(none|[1-9][0-9]*)$/'];}
        $data=$request->validate($rules);
        if(!empty($data['from']) xor !empty($data['to']))throw \Illuminate\Validation\ValidationException::withMessages(['to'=>'حدد بداية ونهاية الفترة، أو اتركهما فارغتين لكل التواريخ.']);
        if(!empty($data['from']) && \Carbon\Carbon::parse($data['from'])->diffInDays($data['to'])>30)throw \Illuminate\Validation\ValidationException::withMessages(['to'=>'الحد الأقصى للفترة المخصصة 31 يومًا شاملًا يوم البداية والنهاية.']);
        return array_filter($data,fn($v)=>$v!==null && $v!=='' && $v!==[]);
    }

    private function values($q,string $column,array $values): void
    {
        $q->where(function($q)use($column,$values){$q->whereIn($column,array_values(array_diff($values,['none'])));if(in_array('none',$values,true))$q->orWhereNull($column);});
    }

    public function query(array $f,string $mode='all')
    {
        $q=HourlyVisit::query();
        if($mode==='latest')$q->whereHas('order',fn($o)=>$o->where('status','active'))->where('hourly_visits.id',function($last){$last->select('last.id')->from('hourly_visits as last')->whereColumn('last.hourly_rental_order_id','hourly_visits.hourly_rental_order_id')->orderByDesc('last.scheduled_start_at')->orderByDesc('last.visit_number')->orderByDesc('last.id')->limit(1);});
        if($mode==='unassigned')$q->whereDoesntHave('workers')->whereNotIn('status',self::CLOSED)->whereHas('order',fn($o)=>$o->whereIn('status',['active','pending_confirmation']));
        if($mode==='past')$q->where('scheduled_end_at','<',now())->whereNotIn('status',self::CLOSED);
        if(!empty($f['from']))$q->where('scheduled_start_at','>=',$f['from'].' 00:00:00');
        if(!empty($f['to']))$q->where('scheduled_start_at','<',\Carbon\Carbon::parse($f['to'])->addDay()->toDateString());
        if(!empty($f['day_part']))$q->whereTime('scheduled_start_at',$f['day_part']==='am'?'<':'>=','12:00:00');
        if(!empty($f['start_time']))$q->where(function($q)use($f){foreach((array)$f['start_time'] as $time)$q->orWhereTime('scheduled_start_at',strlen($time)===5?$time.':00':$time);});
        if(!empty($f['status'])){
            $q->where(function($q)use($f){foreach((array)$f['status'] as $status)$q->orWhere(function($q)use($status){
                if($status==='pending_confirmation')$q->whereHas('order',fn($o)=>$o->where('status',$status));
                elseif($status==='unassigned')$q->whereDoesntHave('workers')->whereNotIn('status',self::CLOSED);
                elseif(in_array($status,['assigned','partial']))$q->whereNotIn('status',self::CLOSED)->whereRaw('(select count(*) from hourly_visit_workers w where w.hourly_visit_id=hourly_visits.id) '.($status==='assigned'?'>=':'<').' workers_count')->when($status==='partial',fn($q)=>$q->whereHas('workers'));
                elseif($status==='missed')$q->where('status','scheduled')->where('scheduled_end_at','<=',now());
                else $q->where('status',$status);
            });});
        }elseif(empty($f['show_cancelled']))$q->whereNotIn('status',self::CANCELLED);
        foreach(['city_id'=>'city_id','team_id'=>'hourly_team_id','leader_id'=>'team_leader_id'] as $field=>$column)if(!empty($f[$field]))$this->values($q,$column,(array)$f[$field]);
        if(!empty($f['worker_id']))$q->where(function($q)use($f){$values=(array)$f['worker_id'];$q->whereHas('workers',fn($w)=>$w->whereIn('maids.id',array_diff($values,['none'])));if(in_array('none',$values))$q->orWhereDoesntHave('workers');});
        if(!empty($f['driver_id']))$q->where(function($q)use($f){$values=(array)$f['driver_id'];$ids=array_diff($values,['none']);$q->whereIn('delivery_driver_id',$ids)->orWhereIn('pickup_driver_id',$ids);if(in_array('none',$values))$q->orWhere(fn($q)=>$q->whereNull('delivery_driver_id')->orWhereNull('pickup_driver_id'));});
        $orderFilters=array_intersect_key($f,array_flip(['payment_status','installment']));
        $orderQuery=app(OrderSearch::class)->query($orderFilters);
        foreach(['admin_id'=>'created_by_admin_id','service_id'=>'service_category_id'] as $field=>$column)if(!empty($f[$field]))$this->values($orderQuery,$column,(array)$f[$field]);
        if(!empty($f['customer_source']))$orderQuery->whereHas('user',function($u)use($f){$values=(array)$f['customer_source'];$u->where(function($q)use($values){$q->whereIn('register_from',array_diff($values,['none']));if(in_array('none',$values))$q->orWhereNull('register_from')->orWhere('register_from','');});});
        foreach(['payment_method','tax_rate'] as $field)if(isset($f[$field]))$orderQuery->where($field,$f[$field]);
        if(isset($f['attribute_id']))$orderQuery->where('package_snapshot->attribute_id',(int)$f['attribute_id']);
        $q->whereIn('hourly_rental_order_id',$orderQuery->select('id'));
        if(!empty($f['search'])){
            $term='%'.$f['search'].'%';
            $q->where(fn($s)=>$s->where('hourly_visits.id',$f['search'])->orWhere('address_text','like',$term)->orWhere('contact_phone','like',$term)
                ->orWhereHas('order',fn($o)=>$o->where('order_number','like',$term)->orWhere('legacy_order_number','like',$term)->orWhereHas('user',fn($u)=>$u->where('name','like',$term)->orWhere('phone','like',$term))));
        }
        return $q;
    }

    public function sort($q,array $f)
    {
        $sort=$f['sort']??'date';$direction=$f['direction']??'asc';
        $column=['date'=>'scheduled_start_at','address'=>'address_text','price'=>'visit_price','duration'=>'planned_hours','status'=>'status'][$sort]??null;
        if($column)$q->orderBy($column,$direction);
        elseif($sort==='customer')$q->orderBy(\App\Models\Hourly\HourlyRentalOrder::select('users.name')->join('users','users.id','=','hourly_rental_orders.user_id')->whereColumn('hourly_rental_orders.id','hourly_visits.hourly_rental_order_id')->limit(1),$direction);
        elseif($sort==='workers')$q->orderBy(\Illuminate\Support\Facades\DB::table('hourly_visit_workers as w')->join('maids','maids.id','=','w.maid_id')->selectRaw('MIN(maids.name)')->whereColumn('w.hourly_visit_id','hourly_visits.id'),$direction);
        else $q->orderBy(\App\Models\Hourly\HourlyDriver::select('name')->whereColumn('id',$sort==='leader'?'hourly_visits.team_leader_id':'hourly_visits.delivery_driver_id')->limit(1),$direction);
        return $q->orderBy('hourly_visits.id',$direction);
    }
}
