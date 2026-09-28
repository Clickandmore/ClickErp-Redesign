<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\{HourlyPackage,HourlyPackageLog};
use App\Services\Hourly\ServiceItemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ServiceItemController extends Controller
{
    public function __construct() { $this->middleware(\App\Http\Middleware\EnsureHourlyCommerceReady::class); }
    public function index(Request $request) {
        $filters=$request->validate(['category'=>['nullable','integer'],'duration'=>['nullable','integer','between:1,1440'],'visits'=>['nullable','integer','between:1,730']]);
        $categories=\App\Models\Hourly\HourlyServiceCategory::ordered()->withCount('packages')->get();
        $category=$categories->firstWhere('id',(int)($filters['category'] ?? 0)) ?? $categories->first();
        $base=HourlyPackage::where('service_category_id',$category?->id ?? 0);
        if (\Illuminate\Support\Facades\Schema::hasTable('hourly_product_attributes')) {
            $base->with('productAttribute.parent');
        }
        $durations=(clone $base)->get(['duration_minutes','hours_per_visit'])->map(fn($p)=>(int)($p->duration_minutes ?? round($p->hours_per_visit*60)))->unique()->sort()->values();
        $visitCounts=(clone $base)->distinct()->orderBy('visits_count')->pluck('visits_count');
        $items=$base->when($filters['duration'] ?? null,fn($q,$d)=>$q->where(fn($q)=>$q->where('duration_minutes',$d)->orWhere(fn($q)=>$q->whereNull('duration_minutes')->where('hours_per_visit',round($d/60,2)))))
            ->when($filters['visits'] ?? null,fn($q,$v)=>$q->where('visits_count',$v))->orderBy('hours_per_visit')->orderBy('visits_count')->get();
        $catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        $publication=[];$categoryPublication=[];
        $allItems=HourlyPackage::whereIn('service_category_id',$categories->pluck('id'))->with(['cities','serviceCategory'])->get()->groupBy('service_category_id');
        foreach ($categories as $tab) {
            $reports=[];
            foreach ($allItems->get($tab->id,collect()) as $item) {
                $publication[$item->id]=$catalog->publicationStatus($item);
                $reports[]=$publication[$item->id];
            }
            $categoryPublication[$tab->id]=$catalog->categoryPublicationStatus($tab,$reports);
        }
        return view('admin.hourly.service-items',compact('categories','category','items','durations','visitCounts','filters','publication','categoryPublication'));
    }
    /** العناصر المستهدفة بالإجراء الجماعي: بمعرّف الفئة، أو بالاسم النصي القديم. */
    private function categoryItems(array $data) {
        return isset($data['service_category_id']) ? HourlyPackage::where('service_category_id',$data['service_category_id']) : HourlyPackage::where('category',$data['category']);
    }
    public function logs(Request $request) {
        $request->validate(['search'=>['nullable','string','max:150']]);
        $logs=HourlyPackageLog::when($request->filled('search'),fn($q)=>$q->where('item_name','like','%'.$request->search.'%'))->latest('id')->paginate(25)->withQueryString();
        return view('admin.hourly.service-item-logs',compact('logs'));
    }
    public function status(Request $request,HourlyPackage $package,ServiceItemService $service) {
        $data=$request->validate(['is_active'=>['required','boolean']]);
        DB::transaction(function()use($package,$data,$service){$package=HourlyPackage::lockForUpdate()->findOrFail($package->id);$before=$service->snapshot($package);$package->fill($data);$package->revision++;$package->save();$service->audit($package,'status',$before);});
        $package->refresh();$catalog=app(\App\Services\Hourly\HourlyWebsiteCatalog::class);
        return response()->json(['is_active'=>(bool)$data['is_active'],'id'=>$package->id,'category_id'=>$package->service_category_id,
            'publication'=>$catalog->publicationStatus($package),
            'category_publication'=>$package->serviceCategory ? $catalog->categoryPublicationStatus($package->serviceCategory) : null]);
    }
    public function price(Request $request,HourlyPackage $package,ServiceItemService $service) {
        $data=$request->validate(['total_price'=>['required','numeric','between:0.01,100000']]);
        DB::transaction(function()use($package,$data,$service){
            $item=HourlyPackage::lockForUpdate()->findOrFail($package->id);
            if($item->deposit_amount>$data['total_price'])throw ValidationException::withMessages(['total_price'=>'السعر أقل من الدفعة المقدمة. عدّل إعدادات الدفع أولاً.']);
            $before=$service->snapshot($item);$item->fill(['total_price'=>$data['total_price'],'price_per_visit'=>round($data['total_price']/$item->visits_count,2),'revision'=>$item->revision+1])->save();$service->audit($item,'price',$before);
        });
        return response()->json(['message'=>'تم تحديث السعر.']);
    }
    public function preview(Request $request,ServiceItemService $service) {
        $data=$request->validate(['service_category_id'=>['required_without:category','nullable','integer'],'category'=>['required_without:service_category_id','nullable','string','max:100'],'type'=>['required','in:prices,shifts,breaks,buffer'],
            'prices'=>['required_if:type,prices','array'],'prices.*'=>['numeric','min:0.01','max:100000'],
            'percentage'=>['nullable','numeric','between:-99.99,1000'],
            'break_minutes'=>['required_if:type,breaks','integer','between:0,240'],
            'minimum_duration'=>['required_if:type,breaks','integer','between:0,720'],
            'buffer_minutes'=>['required_if:type,buffer','integer','between:0,240']]);
        $items=$this->categoryItems($data)->when($data['type']!=='prices',fn($q)=>$q->where('is_active',true))->orderBy('id')->get();
        if($items->isEmpty())throw ValidationException::withMessages(['category'=>'لا توجد عناصر مطابقة.']);
        $times=$data['type']==='shifts' ? $service->shifts($request->input('shifts',[])) : null;
        $rows=[];
        foreach($items as $item){
            $changes=[];
            if($data['type']==='prices') {
                if(!array_key_exists($item->id,$data['prices']))throw ValidationException::withMessages(['prices'=>'أدخل سعر كل عنصر في الفئة.']);
                $price=round((float)$data['prices'][$item->id]*(1+($data['percentage'] ?? 0)/100),2);
                if($price<0.01 || $price>100000)throw ValidationException::withMessages(['prices'=>'السعر الناتج خارج الحدود المسموحة.']);
                if($item->deposit_amount>$price)throw ValidationException::withMessages(['prices'=>'السعر الجديد أقل من الدفعة المقدمة لأحد العناصر.']);
                $changes=['total_price'=>$price,'price_per_visit'=>round($price/$item->visits_count,2)];
            }
            if($data['type']==='shifts')$changes=['start_times'=>$times];
            if($data['type']==='breaks')$changes=['break_minutes'=>(($item->duration_minutes ?? round($item->hours_per_visit*60)) >= $data['minimum_duration'] ? $data['break_minutes'] : 0)];
            if($data['type']==='buffer')$changes=['buffer_minutes'=>$data['buffer_minutes']];
            $original=$item->toArray();
            if($data['type']==='prices')$original['total_price']=$item->total_price ?? $item->price_per_visit*$item->visits_count;
            $rows[]=['id'=>$item->id,'name'=>$item->title,'revision'=>$item->revision,'before'=>array_intersect_key($original,$changes),'after'=>$changes];
        }
        $token=(string)Str::uuid();
        $request->session()->put('hourly_item_preview.'.$token,['rows'=>$rows,'type'=>$data['type'],'admin_id'=>auth('admin')->id(),'expires'=>time()+900]);
        return response()->json(['token'=>$token,'rows'=>$rows]);
    }
    public function apply(Request $request,ServiceItemService $service) {
        $data=$request->validate(['token'=>['required','uuid']]);$key='hourly_item_preview.'.$data['token'];$preview=$request->session()->get($key);
        abort_unless($preview && $preview['expires']>=time() && $preview['admin_id']===auth('admin')->id(),422,'انتهت المعاينة. أعد حسابها.');
        DB::transaction(function()use($preview,$service){
            foreach($preview['rows'] as $row){
                $item=HourlyPackage::lockForUpdate()->find($row['id']);
                if(!$item || $item->revision!=$row['revision'])throw ValidationException::withMessages(['preview'=>'تغير أحد العناصر بعد المعاينة. أعد المعاينة قبل الحفظ.']);
                $before=$service->snapshot($item);$item->fill($row['after']);$item->revision++;$item->save();$service->audit($item,'bulk_'.$preview['type'],$before);
            }
        });
        $request->session()->forget($key);return response()->json(['message'=>'تم حفظ التحديثات.']);
    }
}
