<?php
namespace App\Http\Controllers\Hourly;
use App\Http\Controllers\Controller;
use App\Models\Hourly\HourlyPackage;
use App\Services\Hourly\{HourlyBookingRules,HourlyCategoryConfig};
use Illuminate\Http\Request;
class BookingConfigController extends Controller {
    public function show(Request $request,HourlyPackage $package,HourlyBookingRules $rules,HourlyCategoryConfig $config) {
        $panel=$request->routeIs('admin.*');$category=$rules->category($package);
        abort_unless($package->is_active && $category && $category->is_active && !$category->trashed(),404,__('hourly_api.package_unavailable'));
        if(!$panel)abort_unless(!$package->is_private && $package->show_on_app && $rules->visibleToCustomers($package),404,__('hourly_api.package_unavailable'));
        $data=$request->validate(['city_id'=>['nullable','integer','exists:cities,id'],'quantity'=>['nullable','integer','between:1,50'],'workers'=>['nullable','integer','between:1,20'],'payment_channel'=>['nullable','in:employee,branch']]);
        $channel=$panel?($data['payment_channel']??'employee'):'web_app';
        $result=$config->forCategory($category,$panel?'panel':'web_app',null,$package);
        $result['payment_methods']=$rules->paymentMethods($package,$channel);
        $districts=$category->districts()->where('hourly_service_districts.is_active',true)->wherePivot('is_active',true)
            ->when(isset($data['city_id']),fn($query)=>$query->where('city_id',$data['city_id']));
        $result['districts']=$districts->get(['hourly_service_districts.id','city_id','name'])->map->only(['id','city_id','name']);
        $result['workers_per_visit']=$package->workers_per_visit;
        $result['default_counts']=$rules->defaultCounts($package,$panel?'panel':'web_app');
        $result['attribute_id']=$package->attribute_id;
        $result['attributes']=app(\App\Services\Hourly\ProductAttributeService::class)->groups($package,isset($data['city_id'])?(int)$data['city_id']:null,$panel);
        $result['worker_choices']=[];
        if($result['customer_worker_selection']){
            foreach($category->districts()->where('hourly_service_districts.is_active',true)->wherePivot('is_active',true)->when(isset($data['city_id']),fn($query)=>$query->where('city_id',$data['city_id']))->get() as $district){
                [, $coverage]=$rules->assertDistrictCovered($category,(int)$district->city_id,null,null,null,(int)$district->id);
                $choices=app(\App\Services\Hourly\HourlyWorkerAssignment::class)->candidates($package,$coverage->team_id,(bool)$rules->settings()->value($package,'assign_by_product_attribute'))->get(['maids.id','maids.name']);
                foreach($choices as $choice)$result['worker_choices'][]=['id'=>$choice->id,'name'=>$choice->name,'district_id'=>$district->id];
            }
        }
        if($request->has('workers') || $request->has('quantity')){
            $defaults=$rules->defaultCounts($package,$panel?'panel':'web_app');
            $workers=(int)($data['workers']??$defaults['workers']);
            if($workers<$package->workers_per_visit)\App\Services\Hourly\HourlyOrderService::reject('عدد العاملات أقل من عدد الباقة.');
            $result['quote']=$rules->quote($package,$workers,(int)($data['quantity']??$defaults['quantity']),$panel);
            $result['quote']['presentation']=app(\App\Services\Hourly\HourlyBookingOptions::class)->presentation($package,$result['quote'],$panel?'panel':'web_app');
        }
        return response()->json(['data'=>$result]);
    }
}
